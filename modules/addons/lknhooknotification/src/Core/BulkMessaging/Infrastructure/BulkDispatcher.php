<?php

namespace Lkn\HookNotification\Core\BulkMessaging\Infrastructure;

use Lkn\HookNotification\Core\BulkMessaging\Application\Services\BulkService;
use Lkn\HookNotification\Core\BulkMessaging\Domain\Bulk;
use Lkn\HookNotification\Core\BulkMessaging\Domain\BulkNotification;
use Lkn\HookNotification\Core\BulkMessaging\Domain\BulkStatus;
use Lkn\HookNotification\Core\NotificationQueue\Application\NotificationQueueService;
use Lkn\HookNotification\Core\NotificationQueue\Domain\QueuedNotificationStatus;
use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportStatus;
use Lkn\HookNotification\Core\Notification\Application\Services\NotificationSender;
use Lkn\HookNotification\Core\Notification\Domain\NotificationTemplate;
use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Settings;
use Lkn\HookNotification\Core\Shared\Infrastructure\Result;
use Lkn\HookNotification\Core\Shared\Infrastructure\Singleton;
use Throwable;

/**
 * Used in entrypoint.php
 */
final class BulkDispatcher extends Singleton
{
    private readonly BulkService $bulkMessageRepositoryService;
    private readonly BulkRepository $bulkRepository;
    private readonly NotificationQueueService $notificationQueueService;
    private readonly NotificationSender $notificationSender;

    protected function __construct()
    {
        $this->notificationSender           = NotificationSender::getInstance();
        $this->bulkMessageRepositoryService = new BulkService();
        $this->bulkRepository               = new BulkRepository();
        $this->notificationQueueService     = new NotificationQueueService();
    }

    public function run(): void
    {
        if (lkn_hn_config(Settings::BULK_ENABLE)) {
            add_hook(
                'AfterCronJob',
                999,
                function (): void {
                    try {
                        $this->dispatchBulks();
                    } catch (Throwable $th) {
                        lkn_hn_log('Bulk cron job error', [], ['error' => $th->__toString()]);
                    }
                }
            );
        }
    }

    public function dispatchBulks(): void
    {
        // ── Phase 1: continue any in-progress one-time or recurring run ──────
        if ($this->bulkMessageRepositoryService->isAnyInProgress()) {
            $bulks = $this->bulkMessageRepositoryService->getInProgressBulks();

            foreach ($bulks as $bulk) {
                $this->processBulk($bulk);
            }

            return; // one campaign at a time — do not start another this cycle
        }

        // ── Phase 2: start the next due recurring campaign (FIFO by next_run_at) ──
        $activeDue = $this->bulkMessageRepositoryService->getActiveCampaignsDue();

        if (empty($activeDue)) {
            return;
        }

        $bulk = $activeDue[0]; // oldest next_run_at first

        $this->startRecurringRun($bulk);
    }

    // -------------------------------------------------------------------------
    // Private — send loop
    // -------------------------------------------------------------------------

    private function processBulk(Bulk $bulk): void
    {
        // Guard: if status was changed externally (cancel/pause) while this cron cycle runs, stop immediately.
        $freshBulk = $this->bulkMessageRepositoryService->getBulk($bulk->id);
        if (!$freshBulk || $freshBulk->status !== BulkStatus::IN_PROGRESS) {
            return;
        }

        [
            $waitingBulkMessages,
            $totalWaitingBulkMessages,
            $totalBulkMessages,
        ] = $this->bulkMessageRepositoryService->getInProgressBulkMessages(
            $bulk->id,
            limit: $bulk->maxConcurrency
        );

        foreach ($waitingBulkMessages as $queuedBulkMessage) {
            $template = new NotificationTemplate(
                $bulk->platform,
                null,
                $bulk->template,
                $bulk->platformPayload ?? []
            );

            $notification = new BulkNotification();
            $notification->setTemplates([$template]);

            $platformResponse = $this->notificationSender->send(
                $notification,
                [
                    'client_id' => $queuedBulkMessage->clientId,
                    ...$bulk->filters,
                ],
                queueId: $queuedBulkMessage->id,
            );

            if (is_null($queuedBulkMessage->id)) {
                continue;
            }

            if ($platformResponse instanceof Result) {
                $this->bulkMessageRepositoryService->updateBulkMessageStatus(
                    $queuedBulkMessage->id,
                    QueuedNotificationStatus::ERROR,
                );

                $totalWaitingBulkMessages--;

                continue;
            }

            $newStatus = $platformResponse->status === NotificationReportStatus::SENT
                ? QueuedNotificationStatus::SENT
                : QueuedNotificationStatus::ERROR;

            $this->bulkMessageRepositoryService->updateBulkMessageStatus(
                $queuedBulkMessage->id,
                $newStatus
            );

            $totalWaitingBulkMessages--;
        }

        $this->bulkMessageRepositoryService->updateBulkMessageProgress(
            $bulk->id,
            $totalWaitingBulkMessages,
            $totalBulkMessages
        );

        // After sending this batch, check if the run is now complete
        $updatedBulk = $this->bulkMessageRepositoryService->getBulk($bulk->id);

        if ($updatedBulk && $updatedBulk->progress >= 100) {
            if ($updatedBulk->isRecurring()) {
                $this->finalizeRecurringRun($updatedBulk);
            } else {
                $this->finalizeOnceRun($updatedBulk);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Private — recurring campaign lifecycle
    // -------------------------------------------------------------------------

    /**
     * Starts a new run for a recurring ACTIVE campaign:
     * 1. Atomically claims the campaign (active → in_progress) to prevent concurrent cron runs
     * 2. Re-evaluates filters → clears old queue → enqueues current clients
     * 3. Creates a campaign_run record and processes the first batch
     */
    private function startRecurringRun(Bulk $bulk): void
    {
        try {
            // Atomic guard: only the process that wins the UPDATE WHERE status='active' proceeds.
            // Prevents two concurrent cron runs from both initializing and double-sending the campaign.
            if ($this->bulkRepository->atomicSetInProgress($bulk->id) === 0) {
                return;
            }

            // Re-evaluate filters dynamically
            $clientIds = array_column(
                $this->bulkMessageRepositoryService->getClientsByFilter($bulk->filters),
                'value'
            );

            if (empty($clientIds)) {
                lkn_hn_log('Recurring campaign: no clients matched filters', ['bulk_id' => $bulk->id], []);

                // Still advance to next run so the campaign doesn't get stuck
                $this->bulkMessageRepositoryService->advanceRecurringCampaign($bulk);

                return;
            }

            // Clear any leftover queue entries from previous run
            $this->bulkRepository->clearQueueForBulk($bulk->id);

            // Enqueue clients for this run
            $this->notificationQueueService->insertFromBulkToQueue($bulk->id, $clientIds);

            // Create run history record
            $this->bulkRepository->insertCampaignRun($bulk->id, (new \DateTime())->format('Y-m-d H:i:s'));

            // status/progress already set by atomicSetInProgress — no redundant updateBulk needed

            // Immediately process the first batch this cron cycle
            $freshBulk = $this->bulkMessageRepositoryService->getBulk($bulk->id);

            if ($freshBulk) {
                $this->processBulk($freshBulk);
            }
        } catch (Throwable $th) {
            lkn_hn_log('Recurring campaign start error', ['bulk_id' => $bulk->id], ['error' => $th->__toString()]);
        }
    }

    /**
     * Closes the open campaign_run record for a one-time campaign that just completed.
     * Status is already COMPLETED (set by updateBulkMessageProgress); this just tidies the run log.
     */
    private function finalizeOnceRun(Bulk $bulk): void
    {
        try {
            $openRun = $this->bulkRepository->getOpenCampaignRun($bulk->id);

            if ($openRun) {
                $clientsReached = $this->notificationQueueService->countQueuedNotifications(
                    $bulk->id,
                    status: QueuedNotificationStatus::SENT,
                );

                $this->bulkRepository->completeCampaignRun($openRun->id, $clientsReached);
            }
        } catch (Throwable $th) {
            lkn_hn_log('Once campaign finalize error', ['bulk_id' => $bulk->id], ['error' => $th->__toString()]);
        }
    }

    /**
     * Called when a recurring run reaches 100%: completes the run record and advances schedule.
     */
    private function finalizeRecurringRun(Bulk $bulk): void
    {
        try {
            $openRun = $this->bulkRepository->getOpenCampaignRun($bulk->id);

            if ($openRun) {
                $clientsReached = $this->notificationQueueService->countQueuedNotifications(
                    $bulk->id,
                    status: QueuedNotificationStatus::SENT,
                );

                $this->bulkRepository->completeCampaignRun($openRun->id, $clientsReached);
            }

            // Calculate next run date or mark as completed
            $this->bulkMessageRepositoryService->advanceRecurringCampaign($bulk);
        } catch (Throwable $th) {
            lkn_hn_log('Recurring campaign finalize error', ['bulk_id' => $bulk->id], ['error' => $th->__toString()]);
        }
    }
}
