<?php

namespace Lkn\HookNotification\Core\BulkMessaging\Http;

use DateTime;
use Lkn\HookNotification\Core\BulkMessaging\Domain\BulkStatus;
use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Platforms;

final class NewBulkRequest
{
    /**
     * @param BulkStatus|null                    $status
     * @param string|null                        $title
     * @param string|null                        $descrip
     * @param Platforms|null                     $platform
     * @param DateTime|null                      $startAt
     * @param int|null                           $maxConcurrency
     * @param array<string, array<string>>|null  $filters
     * @param string|null                        $template
     * @param string                             $recurrenceType  once|daily|weekly|monthly|custom
     * @param array|null                         $recurrenceConfig
     * @param DateTime|null                      $endAt
     */
    public function __construct(
        public readonly ?BulkStatus $status,
        public readonly ?string $title,
        public readonly ?string $descrip,
        public readonly ?Platforms $platform,
        public readonly ?DateTime $startAt,
        public readonly ?int $maxConcurrency,
        public readonly ?array $filters,
        public readonly ?string $template,
        public readonly string $recurrenceType = 'once',
        public readonly ?array $recurrenceConfig = null,
        public readonly ?DateTime $endAt = null,
    ) {
    }

    /**
     * @param  array<string, mixed> $request
     * @return array<string, mixed>
     */
    public static function parseFiltersFromRequest(array $request): array
    {
        return [
            'client_status'        => $request['client-status'] ?? [],
            'client_locale'        => $request['client-locale'] ?? [],
            'client_country'       => $request['client-country'] ?? [],
            'services'             => $request['services'] ?? [],
            'service_status'       => $request['service-status'] ?? [],
            'client_ids'           => $request['client-ids'] ?? [],
            'client_groups'        => $request['client-groups'] ?? [],
            'not_sending_clients'  => $request['not-sending-clients'] ?? [],
        ];
    }

    /**
     * Parses recurrence config from a form POST request.
     *
     * @param  array<string, mixed> $request
     * @return array
     */
    public static function parseRecurrenceConfigFromRequest(array $request): array
    {
        $type = $request['recurrence-type'] ?? 'once';

        return match ($type) {
            'daily'   => ['interval' => (int) ($request['recurrence-interval'] ?? 1)],
            'weekly'  => [
                'days_of_week' => array_map('intval', (array) ($request['recurrence-days-of-week'] ?? [1])),
                'interval'     => (int) ($request['recurrence-interval'] ?? 1),
            ],
            'monthly' => ['day_of_month' => $request['recurrence-day-of-month'] ?? 1],
            'custom'  => ['interval_days' => (int) ($request['recurrence-interval-days'] ?? 1)],
            default   => [],
        };
    }
}
