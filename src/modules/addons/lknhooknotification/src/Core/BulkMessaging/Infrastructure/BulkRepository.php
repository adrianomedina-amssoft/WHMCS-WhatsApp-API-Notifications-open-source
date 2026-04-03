<?php

namespace Lkn\HookNotification\Core\BulkMessaging\Infrastructure;

use DateTime;
use Lkn\HookNotification\Core\BulkMessaging\Domain\BulkStatus;
use Lkn\HookNotification\Core\NotificationQueue\Domain\QueuedNotificationStatus;
use Lkn\HookNotification\Core\Shared\Infrastructure\Repository\BaseRepository;

final class BulkRepository extends BaseRepository
{

    public function getInProgressBulkMessages(int $bulkId): array
    {
        $result = $this->query
                ->table('mod_lkn_hook_notification_notif_queue')
                ->where('status', QueuedNotificationStatus::WAITING->value)
                ->where('bulk_id', $bulkId)
                ->get();

        return $result->toArray();
    }

    /**
     * @return object[]
     */
    public function getInProgressBulks(): array
    {
        $result = $this->query
                ->table('mod_lkn_hook_notification_bulks')
                ->where('status', '=', BulkStatus::IN_PROGRESS->value)
                ->where('progress', '!=', 100)
                ->where('start_at', '<=', (new DateTime())->format('Y-m-d H:i:s'))
                ->get();

        return $result->toArray();
    }

    /**
     * Returns ACTIVE campaigns whose next_run_at has arrived, ordered by oldest first.
     *
     * @return object[]
     */
    public function getActiveCampaignsDue(): array
    {
        $result = $this->query
            ->table('mod_lkn_hook_notification_bulks')
            ->where('status', '=', BulkStatus::ACTIVE->value)
            ->where('next_run_at', '<=', (new DateTime())->format('Y-m-d H:i:s'))
            ->orderBy('next_run_at', 'asc')
            ->get();

        return $result->toArray();
    }

    /**
     * Returns true if any campaign is currently IN_PROGRESS.
     */
    public function isAnyInProgress(): bool
    {
        return $this->query
            ->table('mod_lkn_hook_notification_bulks')
            ->where('status', '=', BulkStatus::IN_PROGRESS->value)
            ->where('progress', '!=', 100)
            ->exists();
    }

    /**
     * @return array
     */
    public function getBulk(int $bulkId): array
    {
        $result = $this->query
                ->table('mod_lkn_hook_notification_bulks')
                ->where('id', $bulkId)
                ->first();

        return (array) $result;
    }

    public function getBulks(): array
    {
        $result = $this->query
                ->table('mod_lkn_hook_notification_bulks')
                ->orderBy('id', 'desc')
                ->get();

        return $result->toArray();
    }

    public function insertBulk(
        string $title,
        string $status,
        string $description,
        string $platform,
        string $startAt,
        int $maxConcurrency,
        array $filters,
        float $progress,
        string $template,
        ?string $platformPayload,
        string $recurrenceType = 'once',
        ?string $recurrenceConfig = null,
        ?string $nextRunAt = null,
        ?string $endAt = null,
    ): int {
        $data = [
            'status'           => $status,
            'title'            => $title,
            'description'      => $description,
            'platform'         => $platform,
            'start_at'         => $startAt,
            'max_concurrency'  => $maxConcurrency,
            'filters'          => lkn_hn_safe_json_encode($filters),
            'progress'         => $progress,
            'template'         => $template,
            'platform_payload' => $platformPayload,
            'recurrence_type'  => $recurrenceType,
        ];

        if ($recurrenceConfig !== null) {
            $data['recurrence_config'] = $recurrenceConfig;
        }

        if ($nextRunAt !== null) {
            $data['next_run_at'] = $nextRunAt;
        }

        if ($endAt !== null) {
            $data['end_at'] = $endAt;
        }

        return $this->query
            ->table('mod_lkn_hook_notification_bulks')
            ->insertGetId($data);
    }

    public function updateBulk(
        int $bulkId,
        ?string $status = null,
        ?string $completedAt = null,
        ?float $progress = null,
        ?string $startAt = null,
        ?string $nextRunAt = null,
    ): int {
        $updateArray = [];

        $query = $this->query->table('mod_lkn_hook_notification_bulks')->where('id', $bulkId);

        if ($status) {
            $updateArray['status'] = $status;
        }

        if ($completedAt) {
            $updateArray['completed_at'] = $completedAt;
        }

        if ($startAt) {
            $updateArray['start_at'] = $startAt;
        }

        if (!is_null($progress)) {
            $updateArray['progress'] = $progress;
        }

        if ($nextRunAt !== null) {
            $updateArray['next_run_at'] = $nextRunAt;
        }

        $result = $query->update($updateArray);

        lkn_hn_log(
            'Update bulk status',
            [
                'bulk_id'      => $bulkId,
                'status'       => $status,
                'completed_at' => $completedAt,
                'start_at'     => $startAt,
                'progress'     => $progress,
                'next_run_at'  => $nextRunAt,
                'update_array' => $updateArray,
            ],
            [
                'result' => $result,
            ],
        );

        return $result;
    }

    // -------------------------------------------------------------------------
    // Campaign runs (dispatch history)
    // -------------------------------------------------------------------------

    public function insertCampaignRun(int $campaignId, string $startedAt): int
    {
        return $this->query
            ->table('mod_lkn_hook_notification_campaign_runs')
            ->insertGetId([
                'campaign_id' => $campaignId,
                'started_at'  => $startedAt,
                'status'      => 'in_progress',
            ]);
    }

    public function completeCampaignRun(int $runId, int $clientsReached): void
    {
        $this->query
            ->table('mod_lkn_hook_notification_campaign_runs')
            ->where('id', $runId)
            ->update([
                'completed_at'    => (new DateTime())->format('Y-m-d H:i:s'),
                'clients_reached' => $clientsReached,
                'status'          => 'completed',
            ]);
    }

    /**
     * Marks any in-progress run for a campaign as failed (used when campaign is aborted).
     */
    public function failOpenCampaignRuns(int $campaignId): void
    {
        $this->query
            ->table('mod_lkn_hook_notification_campaign_runs')
            ->where('campaign_id', $campaignId)
            ->where('status', 'in_progress')
            ->update([
                'completed_at' => (new DateTime())->format('Y-m-d H:i:s'),
                'status'       => 'failed',
            ]);
    }

    /**
     * @return object[]
     */
    public function getCampaignRuns(int $campaignId): array
    {
        return $this->query
            ->table('mod_lkn_hook_notification_campaign_runs')
            ->where('campaign_id', $campaignId)
            ->orderBy('started_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Deletes all queue entries for a bulk (used before re-queuing for recurring run).
     */
    public function clearQueueForBulk(int $bulkId): void
    {
        $this->query
            ->table('mod_lkn_hook_notification_notif_queue')
            ->where('bulk_id', $bulkId)
            ->delete();
    }

    /**
     * Updates all editable fields of a campaign (title, description, filters, template, recurrence, etc.).
     */
    public function updateBulkFull(
        int $bulkId,
        string $title,
        string $description,
        string $startAt,
        int $maxConcurrency,
        array $filters,
        string $template,
        ?string $platformPayload,
        string $recurrenceType,
        ?string $recurrenceConfig,
        ?string $nextRunAt,
        ?string $endAt,
    ): int {
        $data = [
            'title'             => $title,
            'description'       => $description,
            'start_at'          => $startAt,
            'max_concurrency'   => $maxConcurrency,
            'filters'           => lkn_hn_safe_json_encode($filters),
            'template'          => $template,
            'platform_payload'  => $platformPayload,
            'recurrence_type'   => $recurrenceType,
            'recurrence_config' => $recurrenceConfig,
            'next_run_at'       => $nextRunAt,
            'end_at'            => $endAt,
        ];

        return $this->query
            ->table('mod_lkn_hook_notification_bulks')
            ->where('id', $bulkId)
            ->update($data);
    }

    /**
     * Permanently deletes a campaign, its queue entries, and its run history.
     */
    public function deleteBulk(int $bulkId): bool
    {
        // Clear queue first (may not have a DB-level CASCADE)
        $this->clearQueueForBulk($bulkId);

        // campaign_runs have ON DELETE CASCADE so they are removed automatically
        return (bool) $this->query
            ->table('mod_lkn_hook_notification_bulks')
            ->where('id', $bulkId)
            ->delete();
    }
}
