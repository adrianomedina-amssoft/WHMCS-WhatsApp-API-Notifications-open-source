<?php

namespace Lkn\HookNotification\Core\BulkMessaging\Application\Services;

use DateTime;
use DateTimeZone;
use Lkn\HookNotification\Core\BulkMessaging\Domain\Bulk;
use Lkn\HookNotification\Core\BulkMessaging\Domain\BulkStatus;
use Lkn\HookNotification\Core\BulkMessaging\Http\NewBulkRequest;
use Lkn\HookNotification\Core\BulkMessaging\Infrastructure\BulkRepository;
use Lkn\HookNotification\Core\NotificationQueue\Application\NotificationQueueService;
use Lkn\HookNotification\Core\NotificationQueue\Domain\QueuedNotificationStatus;
use Lkn\HookNotification\Core\Notification\Application\Services\NotificationService;
use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Lkn\HookNotification\Core\Shared\Infrastructure\Result;
use Throwable;
use WHMCS\Database\Capsule;

final class BulkService
{
    private readonly BulkRepository $bulkRepository;
    private readonly NotificationQueueService $notificationQueueService;
    private readonly NotificationService $notificationService;
    private readonly CampaignScheduler $scheduler;

    public function __construct()
    {
        $this->bulkRepository           = new BulkRepository();
        $this->notificationQueueService = new NotificationQueueService();
        $this->notificationService      = new NotificationService();
        $this->scheduler                = new CampaignScheduler();
    }

    public function updateBulkMessageStatus(
        int $queuedNotificationid,
        QueuedNotificationStatus $status
    ): bool {
        return $this->notificationQueueService->updateQueuedNotificationStatus(
            $queuedNotificationid,
            $status
        );
    }

    public function updateBulkStatus(int $bulkId, BulkStatus $status): bool
    {
        $result = $this->bulkRepository->updateBulk($bulkId, status: $status->value);

        if ($result) {
            if ($status === BulkStatus::ABORTED) {
                [$inProgressBulkMessages, $x, $y] = $this->getInProgressBulkMessages($bulkId, 99999999);

                foreach ($inProgressBulkMessages as $message) {
                    $this->updateBulkMessageStatus($message->id, QueuedNotificationStatus::ABORTED);
                }
            }
        }

        return $result;
    }

    public function updateBulkMessageProgress(
        int $bulkId,
        int $totalWaitingBulkMessages,
        int $totalBulkMessages,
    ) {
        $processed   = $totalBulkMessages - $totalWaitingBulkMessages;
        $progress    = ($processed / $totalBulkMessages) * 100;
        $progress    = min(max($progress, 0), 100);
        $completedAt = null;
        $status      = BulkStatus::IN_PROGRESS->value;

        if ($progress === 100) {
            $completedAt = (new DateTime())->format('Y-m-d H:i:s');
            $status      = BulkStatus::COMPLETED->value;
        }

        $this->bulkRepository->updateBulk(
            $bulkId,
            progress: $progress,
            completedAt: $completedAt,
            status: $status,
        );
    }

    /**
     * Called by BulkDispatcher after a recurring campaign run completes.
     * Calculates next_run_at and sets status back to ACTIVE (or COMPLETED if end_at passed).
     */
    public function advanceRecurringCampaign(Bulk $bulk): void
    {
        $nextRunAt = $this->scheduler->calculateNextRun(
            $bulk->recurrenceType,
            $bulk->recurrenceConfig ?? [],
            new DateTime(),
            $bulk->endAt,
        );

        if ($nextRunAt) {
            $this->bulkRepository->updateBulk(
                $bulk->id,
                status: BulkStatus::ACTIVE->value,
                nextRunAt: $nextRunAt->format('Y-m-d H:i:s'),
                progress: 0.0,
            );
        } else {
            // End date reached or no next run
            $this->bulkRepository->updateBulk(
                $bulk->id,
                status: BulkStatus::COMPLETED->value,
                completedAt: (new DateTime())->format('Y-m-d H:i:s'),
            );
        }
    }

    /**
     * @return Bulk[]
     */
    public function getInProgressBulks(): array
    {
        $rawInProgressBulks = $this->bulkRepository->getInProgressBulks();

        return array_map([$this, 'hydrateBulk'], $rawInProgressBulks);
    }

    /**
     * Returns ACTIVE campaigns whose next_run_at has arrived (ordered oldest first).
     *
     * @return Bulk[]
     */
    public function getActiveCampaignsDue(): array
    {
        return array_map([$this, 'hydrateBulk'], $this->bulkRepository->getActiveCampaignsDue());
    }

    public function isAnyInProgress(): bool
    {
        return $this->bulkRepository->isAnyInProgress();
    }

    /**
     * @param integer $bulkId
     * @param integer $limit
     *
     * @return array
     */
    public function getInProgressBulkMessages(
        int $bulkId,
        int $limit
    ): array {
        $waitingBulkMessages = $this->notificationQueueService->getQueuedNotifications(
            bulkId: $bulkId,
            status: QueuedNotificationStatus::WAITING,
            limit: $limit
        );

        $totalWaitingBulkMessages = $this->notificationQueueService->countQueuedNotifications(
            $bulkId,
            status: QueuedNotificationStatus::WAITING,
        );

        $totalBulkMessages = $this->notificationQueueService->countQueuedNotifications($bulkId);

        return [$waitingBulkMessages, $totalWaitingBulkMessages, $totalBulkMessages];
    }

    /**
     * @param  array<string, array<string>>|null $filters
     * @param boolean                            $allClients
     *
     * @return array
     */
    public function getClientsByFilter(array $filters, bool $allClients = false): array
    {
        $query = Capsule::table('tblclients')
            ->leftJoin('tblhosting', 'tblclients.id', '=', 'tblhosting.userid');

        if (!$allClients && !empty($filters['not_sending_clients'])) {
            $query->whereNotIn('tblclients.id', $filters['not_sending_clients']);
        }

        if (!empty($filters['client_locale'])) {
            $locales = (array) $filters['client_locale'];

            $query->where(function ($q) use ($locales) {
                $hasDefault    = in_array('default', $locales, true) || in_array('', $locales, true);
                $explicitLocales = array_values(array_filter($locales, fn($val) => $val !== 'default' && $val !== ''));

                if (!empty($explicitLocales)) {
                    $q->whereIn('tblclients.language', $explicitLocales);
                }

                if ($hasDefault) {
                    if (empty($explicitLocales)) {
                        $q->whereNull('tblclients.language')
                          ->orWhere('tblclients.language', '');
                    } else {
                        $q->orWhereNull('tblclients.language')
                          ->orWhere('tblclients.language', '');
                    }
                }
            });
        }

        if (!empty($filters['client_status'])) {
            $query->whereIn('tblclients.status', $filters['client_status']);
        }

        if (!empty($filters['client_country'])) {
            $query->whereIn('tblclients.country', $filters['client_country']);
        }

        if (!empty($filters['services'])) {
            $query->whereIn('tblhosting.packageid', $filters['services']);
        }

        if (!empty($filters['service_status'])) {
            $query->whereIn('tblhosting.domainstatus', $filters['service_status']);
        }

        // Client groups filter
        if (!empty($filters['client_groups'])) {
            $query->whereIn('tblclients.groupid', $filters['client_groups']);
        }

        $query
            ->selectRaw(
                "tblclients.id as value, CONCAT(tblclients.firstname, ' ', tblclients.lastname) as label"
            )
            ->distinct();

        return array_map(
            fn ($item) => (array) $item,
            $query->get()->toArray()
        );
    }

    public function getBulk(int $bulkId): ?Bulk
    {
        $rawBulk = $this->bulkRepository->getBulk($bulkId);

        if (!$rawBulk) {
            return null;
        }

        return $this->hydrateBulkFromArray($rawBulk);
    }

    /**
     * @return Bulk[]
     */
    public function getBulks(): array
    {
        $rawBulks = $this->bulkRepository->getBulks();

        return array_map([$this, 'hydrateBulk'], $rawBulks);
    }

    /**
     * @param NewBulkRequest $newBulkRequest
     * @param array<mixed>   $rawFormPost
     *
     * @return Result
     */
    public function createBulk(
        NewBulkRequest $newBulkRequest,
        array $rawFormPost
    ): Result {
        $template        = null;
        $platformPayload = null;

        if ($newBulkRequest->platform === Platforms::WHATSAPP) {
            $platformPayloadResult = $this->notificationService->handleWhatsAppPlatformPayloadForm($rawFormPost);

            if (!$platformPayloadResult->data) {
                lkn_hn_log(
                    'bulk: parse meta whatsapp template',
                    ['new_bulk_request' => $newBulkRequest, 'raw_form_post' => $rawFormPost],
                    ['platform_payload_result' => $platformPayloadResult->toArray()]
                );

                return lkn_hn_result('error', msg: lkn_hn_lang('Unable to parse Meta WhatsApp template.'));
            }

            $template        = $platformPayloadResult->data['template'];
            $platformPayload = $platformPayloadResult->data['platformPayload'];
        } else {
            $template = $newBulkRequest->template;
        }

        $isRecurring     = $newBulkRequest->recurrenceType !== 'once';
        $recurrenceConfig = $newBulkRequest->recurrenceConfig
            ? lkn_hn_safe_json_encode($newBulkRequest->recurrenceConfig)
            : null;

        // For recurring campaigns the initial status is ACTIVE; they don't run immediately.
        // next_run_at is set to startAt so the dispatcher picks it up on the right date.
        $status    = $isRecurring ? BulkStatus::ACTIVE->value : BulkStatus::IN_PROGRESS->value;
        $nextRunAt = $isRecurring && $newBulkRequest->startAt
            ? $newBulkRequest->startAt->format('Y-m-d H:i:s')
            : null;

        $bulkId = $this->bulkRepository->insertBulk(
            title:            $newBulkRequest->title ?? '',
            status:           $status,
            description:      $newBulkRequest->descrip ?? '',
            platform:         $newBulkRequest->platform->value ?? '',
            startAt:          $newBulkRequest->startAt ? $newBulkRequest->startAt->format('Y-m-d H:i:s') : '',
            maxConcurrency:   $newBulkRequest->maxConcurrency ?? 25,
            filters:          $newBulkRequest->filters ?? [],
            progress:         0.0,
            template:         $template ?? '',
            platformPayload:  $platformPayload ? lkn_hn_safe_json_encode($platformPayload) : null,
            recurrenceType:   $newBulkRequest->recurrenceType,
            recurrenceConfig: $recurrenceConfig,
            nextRunAt:        $nextRunAt,
            endAt:            $newBulkRequest->endAt ? $newBulkRequest->endAt->format('Y-m-d H:i:s') : null,
        );

        if (!$bulkId) {
            return lkn_hn_result(code: 'unable-to-create-bulk');
        }

        // Recurring campaigns: do NOT pre-queue clients — re-evaluate on each dispatch
        if ($isRecurring) {
            return lkn_hn_result(code: 'success', data: ['bulk_id' => $bulkId]);
        }

        // One-time: queue clients now
        $clientIds = array_column($this->getClientsByFilter($newBulkRequest->filters), 'value');

        $result = $this->notificationQueueService->insertFromBulkToQueue($bulkId, $clientIds);

        if (!$result) {
            return lkn_hn_result(code: 'unable-to-queue-bulk-messages');
        }

        return lkn_hn_result(code: 'success', data: ['bulk_id' => $bulkId]);
    }

    /**
     * Duplicates a campaign: copies all fields, resets progress/status/runs.
     */
    public function duplicateCampaign(int $id): Result
    {
        try {
            $bulk = $this->getBulk($id);

            if (!$bulk) {
                return lkn_hn_result('error', errors: ['message' => "Campaign not found: {$id}"]);
            }

            $isRecurring = $bulk->isRecurring();
            $newStatus   = $isRecurring ? BulkStatus::ACTIVE->value : BulkStatus::IN_PROGRESS->value;

            $newId = $this->bulkRepository->insertBulk(
                title:            lkn_hn_lang('Copy of') . ' ' . $bulk->title,
                status:           $newStatus,
                description:      $bulk->description ?? '',
                platform:         $bulk->platform->value,
                startAt:          (new DateTime())->modify('+1 hour')->format('Y-m-d H:i:s'),
                maxConcurrency:   $bulk->maxConcurrency,
                filters:          $bulk->filters,
                progress:         0.0,
                template:         $bulk->template,
                platformPayload:  $bulk->platformPayload ? lkn_hn_safe_json_encode($bulk->platformPayload) : null,
                recurrenceType:   $bulk->recurrenceType,
                recurrenceConfig: $bulk->recurrenceConfig ? lkn_hn_safe_json_encode($bulk->recurrenceConfig) : null,
                nextRunAt:        $isRecurring ? (new DateTime())->modify('+1 hour')->format('Y-m-d H:i:s') : null,
                endAt:            $bulk->endAt ? $bulk->endAt->format('Y-m-d H:i:s') : null,
            );

            if (!$newId) {
                return lkn_hn_result('error', errors: ['message' => 'Failed to insert duplicate']);
            }

            return lkn_hn_result('success', data: ['bulk_id' => $newId]);
        } catch (Throwable $th) {
            return lkn_hn_result('error', errors: ['exception' => $th->getMessage()]);
        }
    }

    public function pauseCampaign(int $id): Result
    {
        try {
            $bulk = $this->getBulk($id);

            if (!$bulk) {
                return lkn_hn_result('error', errors: ['message' => "Campaign not found: {$id}"]);
            }

            if (!$bulk->status->canPause()) {
                return lkn_hn_result('error', errors: ['message' => 'Campaign cannot be paused in current status.']);
            }

            $this->bulkRepository->updateBulk($id, status: BulkStatus::PAUSED->value);

            return lkn_hn_result('success');
        } catch (Throwable $th) {
            return lkn_hn_result('error', errors: ['exception' => $th->getMessage()]);
        }
    }

    public function resumeCampaign(int $id): Result
    {
        try {
            $bulk = $this->getBulk($id);

            if (!$bulk) {
                return lkn_hn_result('error', errors: ['message' => "Campaign not found: {$id}"]);
            }

            if (!$bulk->status->canResume()) {
                return lkn_hn_result('error', errors: ['message' => 'Campaign cannot be resumed in current status.']);
            }

            // Recalculate next_run_at from now
            $nextRunAt = $this->scheduler->calculateNextRun(
                $bulk->recurrenceType,
                $bulk->recurrenceConfig ?? [],
                new DateTime(),
                $bulk->endAt,
            );

            $this->bulkRepository->updateBulk(
                $id,
                status: BulkStatus::ACTIVE->value,
                nextRunAt: $nextRunAt ? $nextRunAt->format('Y-m-d H:i:s') : null,
            );

            return lkn_hn_result('success');
        } catch (Throwable $th) {
            return lkn_hn_result('error', errors: ['exception' => $th->getMessage()]);
        }
    }

    public function getBulkReportForView(int $bulkId)
    {
        return $this->notificationQueueService->getQueuedNotifications(
            $bulkId,
            withClient: true,
            withReport: true,
        );
    }

    public function getCampaignRuns(int $bulkId): array
    {
        return $this->bulkRepository->getCampaignRuns($bulkId);
    }

    public function resendBulkMessage(
        int $bulkId,
        int $bulkMessageId,
    ): Result {
        try {
            [$x, $totalWaitingBulkMessages, $totalBulkMessages] = $this->getInProgressBulkMessages($bulkId, 1);

            $result = $this->notificationQueueService->updateQueuedNotificationStatus(
                $bulkMessageId,
                QueuedNotificationStatus::WAITING
            );

            if (!$result) {
                return lkn_hn_result('error', msg: lkn_hn_lang('Unable to update messages status to waiting.'));
            }

            $processedCount = $totalBulkMessages - ($totalWaitingBulkMessages + 1);
            $newProgress    = min(max(($processedCount / $totalBulkMessages) * 100, 0), 100);

            $result = $this->bulkRepository->updateBulk($bulkId, progress: $newProgress);

            return lkn_hn_result(
                $result ? 'success' : 'error',
                msg: $result ? lkn_hn_lang('The message was queued.') : lkn_hn_lang('Unable to update bulk progress.'),
            );
        } catch (Throwable $th) {
            return lkn_hn_result('error', msg: 'Internal error', errors: ['exception' => $th->__toString()]);
        }
    }

    public function sendNow(int $bulkId): bool
    {
        return boolval(
            $this->bulkRepository->updateBulk(
                $bulkId,
                status: BulkStatus::IN_PROGRESS->value,
                startAt: (new DateTime())->format(DateTime::ATOM)
            )
        );
    }

    /**
     * Preview next N scheduled run dates for a campaign.
     *
     * @return DateTime[]
     */
    public function previewNextRuns(int $count, string $recurrenceType, array $config, DateTime $from, ?DateTime $endAt = null): array
    {
        return $this->scheduler->previewNextRuns($count, $recurrenceType, $config, $from, $endAt);
    }

    /**
     * Returns calendar data for next 30 days across all campaigns.
     *
     * @return array
     */
    public function getCalendar(int $days = 30): array
    {
        return $this->scheduler->getCalendarData($this->getBulks(), $days);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function hydrateBulk(object $rawBulk): Bulk
    {
        return new Bulk(
            id:               $rawBulk->id,
            status:           BulkStatus::from($rawBulk->status),
            title:            $rawBulk->title,
            description:      $rawBulk->description,
            platform:         Platforms::from($rawBulk->platform),
            startAt:          new DateTime($rawBulk->start_at),
            maxConcurrency:   $rawBulk->max_concurrency,
            filters:          json_decode($rawBulk->filters, true) ?? [],
            progress:         $rawBulk->progress,
            createdAt:        new DateTime($rawBulk->created_at, new DateTimeZone('America/Sao_Paulo')),
            completedAt:      $rawBulk->completed_at ? new DateTime($rawBulk->completed_at) : null,
            template:         $rawBulk->template,
            platformPayload:  json_decode($rawBulk->platform_payload ?? 'null', true),
            recurrenceType:   $rawBulk->recurrence_type ?? 'once',
            recurrenceConfig: json_decode($rawBulk->recurrence_config ?? 'null', true),
            nextRunAt:        !empty($rawBulk->next_run_at) ? new DateTime($rawBulk->next_run_at) : null,
            endAt:            !empty($rawBulk->end_at) ? new DateTime($rawBulk->end_at) : null,
        );
    }

    private function hydrateBulkFromArray(array $rawBulk): Bulk
    {
        return new Bulk(
            id:               $rawBulk['id'],
            status:           BulkStatus::from($rawBulk['status']),
            title:            $rawBulk['title'],
            description:      $rawBulk['description'] ?? null,
            platform:         Platforms::from($rawBulk['platform']),
            startAt:          new DateTime($rawBulk['start_at']),
            maxConcurrency:   $rawBulk['max_concurrency'],
            filters:          json_decode($rawBulk['filters'], true) ?? [],
            progress:         $rawBulk['progress'],
            createdAt:        new DateTime($rawBulk['created_at']),
            completedAt:      !empty($rawBulk['completed_at']) ? new DateTime($rawBulk['completed_at']) : null,
            template:         $rawBulk['template'],
            platformPayload:  json_decode($rawBulk['platform_payload'] ?? 'null', true),
            recurrenceType:   $rawBulk['recurrence_type'] ?? 'once',
            recurrenceConfig: json_decode($rawBulk['recurrence_config'] ?? 'null', true),
            nextRunAt:        !empty($rawBulk['next_run_at']) ? new DateTime($rawBulk['next_run_at']) : null,
            endAt:            !empty($rawBulk['end_at']) ? new DateTime($rawBulk['end_at']) : null,
        );
    }
}
