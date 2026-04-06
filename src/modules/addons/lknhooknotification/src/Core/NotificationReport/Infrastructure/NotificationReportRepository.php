<?php

namespace Lkn\HookNotification\Core\NotificationReport\Infrastructure;

use DateTime;
use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportStatus;
use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Lkn\HookNotification\Core\Shared\Infrastructure\Hooks;
use Lkn\HookNotification\Core\Shared\Infrastructure\Repository\BaseRepository;

final class NotificationReportRepository extends BaseRepository
{
    public function paginate(int $offset, int $limit, array $filters = [])
    {
        $baseQuery = $this->query->table('mod_lkn_hook_notification_reports');

        if (!empty($filters['status'])) {
            $baseQuery->where('status', $filters['status']);
        }

        if (!empty($filters['platform'])) {
            $baseQuery->where('platform', $filters['platform']);
        }

        if (!empty($filters['notification'])) {
            $baseQuery->where('notification', $filters['notification']);
        }

        if (!empty($filters['date_from'])) {
            $baseQuery->where('created_at', '>=', $filters['date_from'] . ' 00:00:00');
        }

        if (!empty($filters['date_to'])) {
            $baseQuery->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        if (!empty($filters['client'])) {
            $client = $filters['client'];
            $baseQuery->where(function ($q) use ($client) {
                $q->where('client_id', 'like', '%' . $client . '%')
                    ->orWhere('target', 'like', '%' . $client . '%');
            });
        }

        $totalReports = (clone $baseQuery)->count();

        $reports = $baseQuery
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'reports' => $reports->toArray(),
            'totalReports' => $totalReports,
        ];
    }

    public function getDistinctNotifications(): array
    {
        return $this->query->table('mod_lkn_hook_notification_reports')
            ->select('notification')
            ->distinct()
            ->orderBy('notification', 'asc')
            ->pluck('notification')
            ->toArray();
    }

    public function insertReport(
        int $clientId,
        ?int $categoryId,
        ?NotificationReportCategory $reportCategory,
        NotificationReportStatus $reportStatus,
        ?string $reportMsg,
        ?Platforms $platform,
        string $notificationCode,
        ?Hooks $hook,
        ?int $queueId,
        ?string $target,
    ): int {
        // TODO: add also which NotificationTemplate and whmcsHookParams to allow resend?
        return $this->query->table('mod_lkn_hook_notification_reports')
            ->insert([
                'client_id' => $clientId,
                'category_id' => $categoryId,
                'category' => $reportCategory->value,
                'status' => $reportStatus->value,
                'msg' => $reportMsg,
                'platform' => $platform->value,
                'channel' => null,
                'notification' => $notificationCode,
                'hook' => $hook ? $hook->value : null,
                'queue_id' => $queueId,
                'target' => $target,
            ]);
    }

    public function getReportsForCategory(
        NotificationReportCategory $category,
        int $categoryId
    ): array {
        return $this->query
            ->table('mod_lkn_hook_notification_reports')
            ->orderBy('created_at', 'desc')
            ->where('category', $category->value)
            ->where('category_id', $categoryId)
            ->get()
            ->toArray();
    }

    public function getReportsForLastHour()
    {
        $oneHourAgo = (new DateTime())->modify('-1 hour')->format('Y-m-d H:i:s');

        return $this->query
            ->table('mod_lkn_hook_notification_reports')
            ->where('created_at', '>=', $oneHourAgo)
            ->count();
    }

    public function getFailedReports()
    {
        $oneHourAgo = (new DateTime())->modify('-1 hour')->format('Y-m-d H:i:s');

        return $this->query
            ->table('mod_lkn_hook_notification_reports')
            ->where('created_at', '>=', $oneHourAgo)
            ->where('status', '!=', NotificationReportStatus::SENT->value)
            ->count();
    }

    public function getTopNotificationsForLastHour()
    {
        $oneHourAgo = (new DateTime())->modify('-1 hour')->format('Y-m-d H:i:s');

        return $this->query
            ->table('mod_lkn_hook_notification_reports')
            ->select(
                'notification',
                $this->query::table('mod_lkn_hook_notification_reports')->raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', $oneHourAgo)
            ->groupBy('notification')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
