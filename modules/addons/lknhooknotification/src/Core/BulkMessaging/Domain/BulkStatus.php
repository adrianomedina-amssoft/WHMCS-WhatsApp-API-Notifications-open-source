<?php

namespace Lkn\HookNotification\Core\BulkMessaging\Domain;

enum BulkStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case ABORTED     = 'aborted';
    case COMPLETED   = 'completed';
    case ACTIVE      = 'active';
    case PAUSED      = 'paused';

    public static function forView(): array
    {
        return array_map(
            function (BulkStatus $item) {
                return [
                    'value' => $item->value,
                    'label' => $item->label(),
                ];
            },
            self::cases()
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::IN_PROGRESS => lkn_hn_lang('In progress'),
            self::ABORTED     => lkn_hn_lang('Aborted'),
            self::COMPLETED   => lkn_hn_lang('Completed'),
            self::ACTIVE      => lkn_hn_lang('Active'),
            self::PAUSED      => lkn_hn_lang('Paused'),
        };
    }

    public function labelClass(): string
    {
        return match ($this) {
            self::IN_PROGRESS => 'label-info',
            self::ABORTED     => 'label-warning',
            self::COMPLETED   => 'label-success',
            self::ACTIVE      => 'label-success',
            self::PAUSED      => 'label-default',
        };
    }

    /** Returns true when the campaign is in a state where it can be paused */
    public function canPause(): bool
    {
        return $this === self::ACTIVE;
    }

    /** Returns true when the campaign is in a state where it can be resumed */
    public function canResume(): bool
    {
        return $this === self::PAUSED;
    }
}
