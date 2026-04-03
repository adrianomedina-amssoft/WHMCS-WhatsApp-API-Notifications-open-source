<?php

namespace Lkn\HookNotification\Core\BulkMessaging\Application\Services;

use DateTime;
use Lkn\HookNotification\Core\BulkMessaging\Domain\Bulk;

/**
 * Calculates recurrence schedules for campaigns.
 *
 * @since 4.6.0
 */
final class CampaignScheduler
{
    /**
     * Calculates the next run DateTime after $from, given recurrence type and config.
     * Returns null for one-time campaigns or when end_at is exceeded.
     *
     * recurrence_config format by type:
     *   daily:   { "interval": N }           — every N days
     *   weekly:  { "days_of_week": [0..6], "interval": N } — 0=Sun, interval=every N weeks
     *   monthly: { "day_of_month": 5 }  or  { "day_of_month": "first_business" | "last_business" }
     *   custom:  { "interval_days": N }      — every N days (alias for daily with custom label)
     */
    public function calculateNextRun(
        string $recurrenceType,
        array $config,
        DateTime $from,
        ?DateTime $endAt = null
    ): ?DateTime {
        if ($recurrenceType === 'once') {
            return null;
        }

        $next = match ($recurrenceType) {
            'daily'   => $this->nextDaily($config, $from),
            'weekly'  => $this->nextWeekly($config, $from),
            'monthly' => $this->nextMonthly($config, $from),
            'custom'  => $this->nextCustom($config, $from),
            default   => null,
        };

        if ($next && $endAt && $next > $endAt) {
            return null;
        }

        return $next;
    }

    /**
     * Returns the next N scheduled run dates for preview.
     *
     * @return DateTime[]
     */
    public function previewNextRuns(
        int $count,
        string $recurrenceType,
        array $config,
        DateTime $from,
        ?DateTime $endAt = null
    ): array {
        $runs    = [];
        $current = clone $from;

        for ($i = 0; $i < $count; $i++) {
            $next = $this->calculateNextRun($recurrenceType, $config, $current, $endAt);

            if (!$next) {
                break;
            }

            $runs[]  = clone $next;
            $current = $next;
        }

        return $runs;
    }

    /**
     * Returns calendar data for the next $days days grouped by date (Y-m-d).
     * Each day entry: array of ['id', 'title', 'time', 'has_conflict'].
     *
     * @param  Bulk[] $campaigns
     * @return array<string, array<int, array{id:int,title:string,time:string,has_conflict:bool}>>
     */
    public function getCalendarData(array $campaigns, int $days = 30): array
    {
        $calendar = [];
        $now      = new DateTime();
        $end      = (clone $now)->modify("+{$days} days");

        foreach ($campaigns as $bulk) {
            if (!$bulk->isRecurring() && in_array($bulk->status, [
                \Lkn\HookNotification\Core\BulkMessaging\Domain\BulkStatus::COMPLETED,
                \Lkn\HookNotification\Core\BulkMessaging\Domain\BulkStatus::ABORTED,
            ], true)) {
                continue;
            }

            $config        = $bulk->recurrenceConfig ?? [];
            $current       = clone $now;
            $campaignEndAt = $bulk->endAt;

            // For one-time campaigns not yet run, show the start date
            if (!$bulk->isRecurring()) {
                if ($bulk->startAt >= $now && $bulk->startAt <= $end) {
                    $dateKey = $bulk->startAt->format('Y-m-d');
                    $calendar[$dateKey][] = [
                        'id'           => $bulk->id,
                        'title'        => $bulk->title,
                        'time'         => $bulk->startAt->format('H:i'),
                        'has_conflict' => false,
                    ];
                }
                continue;
            }

            // For recurring campaigns, calculate next occurrences within window
            $next = $bulk->nextRunAt ? clone $bulk->nextRunAt : $this->calculateNextRun(
                $bulk->recurrenceType,
                $config,
                $current,
                $campaignEndAt
            );

            $iterations = 0;

            while ($next && $next <= $end && $iterations < 60) {
                if ($next >= $now) {
                    $dateKey = $next->format('Y-m-d');
                    $calendar[$dateKey][] = [
                        'id'           => $bulk->id,
                        'title'        => $bulk->title,
                        'time'         => $next->format('H:i'),
                        'has_conflict' => false,
                    ];
                }

                $next = $this->calculateNextRun($bulk->recurrenceType, $config, $next, $campaignEndAt);
                $iterations++;
            }
        }

        // Mark conflicts (same date with ≥2 entries)
        foreach ($calendar as $date => &$entries) {
            if (count($entries) >= 2) {
                foreach ($entries as &$entry) {
                    $entry['has_conflict'] = true;
                }
            }
        }

        ksort($calendar);

        return $calendar;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function nextDaily(array $config, DateTime $from): DateTime
    {
        $interval = max(1, (int) ($config['interval'] ?? 1));
        $next     = clone $from;
        $next->modify("+{$interval} days");

        return $next;
    }

    private function nextWeekly(array $config, DateTime $from): ?DateTime
    {
        $daysOfWeek = $config['days_of_week'] ?? [1]; // default Monday
        $interval   = max(1, (int) ($config['interval'] ?? 1));

        if (empty($daysOfWeek)) {
            return null;
        }

        sort($daysOfWeek);

        $candidate = clone $from;
        $candidate->modify('+1 day');

        for ($i = 0; $i < 7 * $interval + 7; $i++) {
            $weekday = (int) $candidate->format('w'); // 0=Sun, 6=Sat

            if (in_array($weekday, $daysOfWeek, true)) {
                // Check week interval: compare ISO week number relative to $from
                $weekDiff = (int) $candidate->format('W') - (int) $from->format('W');
                // Simple modulo check; handles year boundaries approximately
                if ($interval <= 1 || ($weekDiff % $interval) === 0) {
                    return $candidate;
                }
            }

            $candidate->modify('+1 day');
        }

        return null;
    }

    private function nextMonthly(array $config, DateTime $from): ?DateTime
    {
        $daySpec = $config['day_of_month'] ?? 1;

        if ($daySpec === 'first_business') {
            return $this->nextMonthlyFirstBusiness($from);
        }

        if ($daySpec === 'last_business') {
            return $this->nextMonthlyLastBusiness($from);
        }

        $targetDay = max(1, min(28, (int) $daySpec));

        // Try same month first
        $candidate = new DateTime($from->format('Y-m') . '-' . str_pad((string)$targetDay, 2, '0', STR_PAD_LEFT)
            . ' ' . $from->format('H:i:s'));

        if ($candidate > $from) {
            return $candidate;
        }

        // Move to next month
        $candidate->modify('+1 month');
        $candidate->setDate((int)$candidate->format('Y'), (int)$candidate->format('m'), $targetDay);

        return $candidate;
    }

    private function nextMonthlyFirstBusiness(DateTime $from): DateTime
    {
        $candidate = new DateTime($from->format('Y-m') . '-01 ' . $from->format('H:i:s'));

        if ($candidate <= $from) {
            $candidate->modify('+1 month');
            $candidate->setDate((int)$candidate->format('Y'), (int)$candidate->format('m'), 1);
        }

        // Advance until Monday–Friday
        while ((int)$candidate->format('N') > 5) {
            $candidate->modify('+1 day');
        }

        return $candidate;
    }

    private function nextMonthlyLastBusiness(DateTime $from): DateTime
    {
        $candidate = new DateTime($from->format('Y-m-t') . ' ' . $from->format('H:i:s')); // last day of month

        if ($candidate <= $from) {
            $candidate->modify('+1 month');
            $candidate->setDate((int)$candidate->format('Y'), (int)$candidate->format('m'), (int)$candidate->format('t'));
        }

        // Move back until Monday–Friday
        while ((int)$candidate->format('N') > 5) {
            $candidate->modify('-1 day');
        }

        return $candidate;
    }

    private function nextCustom(array $config, DateTime $from): DateTime
    {
        $intervalDays = max(1, (int) ($config['interval_days'] ?? 1));
        $next         = clone $from;
        $next->modify("+{$intervalDays} days");

        return $next;
    }
}
