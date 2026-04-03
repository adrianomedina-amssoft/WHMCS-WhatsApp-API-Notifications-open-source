<?php

use Lkn\HookNotification\Core\AdminUI\Http\Controllers\HomepageController;
use Lkn\HookNotification\Core\AdminUI\Http\Controllers\LogsController;
use Lkn\HookNotification\Core\AdminUI\Http\Controllers\SettingsController;
use Lkn\HookNotification\Core\BulkMessaging\Http\Controllers\BulkController;
use Lkn\HookNotification\Core\NotificationReport\Http\Controllers\NotificationReportController;
use Lkn\HookNotification\Core\Notification\Http\Controllers\NotificationController;

return [
    '404' => [
        'class' => [
            HomepageController::class,
            'notFound404',
        ],
    ],
    'home' => [
        'class' => [
            HomepageController::class,
            'viewHomepage',
        ],
    ],
    'changelog' => [
        'class' => [
            HomepageController::class,
            'viewChangelog',
        ],
    ],
    'notifications' => [
        'class' => [
            NotificationController::class,
            'viewNotificationsTable',
        ],
    ],
    'platforms/{platform}/settings' => [
        'class' => [
            SettingsController::class,
            'viewSettings',
        ],
    ],
    'platforms/{platform}/{subpage}/settings' => [
        'class' => [
            SettingsController::class,
            'viewSubPageSettings',
        ],
    ],
    'platforms/{platform}/notifications' => [
        'class' => [
            NotificationController::class,
            'viewNotificationTemplate',
        ],
    ],
    'notification-reports' => [
        'class' => [
            NotificationReportController::class,
            'viewReports',
        ],
    ],
    'notifications/{notif_code}/templates/{tpl_lang}' => [
        'class' => [
            NotificationController::class,
            'viewNotification',
        ],
    ],
    'notifications/new' => [
        'class' => [
            NotificationController::class,
            'viewCreateCustomNotification',
        ],
    ],
    'notifications/{notif_code}/edit' => [
        'class' => [
            NotificationController::class,
            'viewEditCustomNotification',
        ],
    ],
    'notifications/clone' => [
        'class' => [
            NotificationController::class,
            'handleCloneNotification',
        ],
    ],
    'notifications/delete-custom' => [
        'class' => [
            NotificationController::class,
            'handleDeleteCustomNotification',
        ],
    ],
    'bulk/list' => [
        'class' => [
            BulkController::class,
            'viewBulkMessageList',
        ],
    ],
    'bulk/new' => [
        'class' => [
            BulkController::class,
            'viewNewBulkMessage',
        ],
    ],
    'bulk/calendar' => [
        'class' => [
            BulkController::class,
            'viewCalendar',
        ],
    ],
    'bulk/duplicate' => [
        'class' => [
            BulkController::class,
            'handleDuplicateCampaign',
        ],
    ],
    'bulks/{bulkId}/history' => [
        'class' => [
            BulkController::class,
            'viewCampaignHistory',
        ],
    ],
    'bulks/{bulkId}' => [
        'class' => [
            BulkController::class,
            'viewEditBulk',
        ],
    ],
    'logs' => [
        'class' => [
            LogsController::class,
            'viewLogs',
        ],
    ],
];
