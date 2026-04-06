<?php

namespace Lkn\HookNotification\Core\BulkMessaging\Domain;

enum RecurrenceType: string
{
    case ONCE    = 'once';
    case DAILY   = 'daily';
    case WEEKLY  = 'weekly';
    case MONTHLY = 'monthly';
    case CUSTOM  = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::ONCE    => lkn_hn_lang('One-time'),
            self::DAILY   => lkn_hn_lang('Daily'),
            self::WEEKLY  => lkn_hn_lang('Weekly'),
            self::MONTHLY => lkn_hn_lang('Monthly'),
            self::CUSTOM  => lkn_hn_lang('Custom'),
        };
    }

    public static function forView(): array
    {
        return array_map(
            fn (RecurrenceType $t) => ['value' => $t->value, 'label' => $t->label()],
            self::cases()
        );
    }
}
