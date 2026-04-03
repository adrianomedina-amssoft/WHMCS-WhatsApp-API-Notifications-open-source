<?php

namespace Lkn\HookNotification\Core\WHMCS\SafePasswordReset;

use Lkn\HookNotification\Core\Notification\Application\Services\NotificationService;
use Lkn\HookNotification\Core\Shared\Infrastructure\I18n\I18n;
use Throwable;

final class SafePasswordResetController {
    private readonly NotificationService $notificationService;


    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    public function handleClientAreaPasswordReset(array $whmcsHookParams): void
    {
        try {
            if (!$this->notificationService->isNotificationEnabled('SafePasswordReset')) {
                return;
            }

            $clientLanguage = $whmcsHookParams['language'];

            $translations     = I18n::getInstance()->getTranslationsForCurrentLanguage($clientLanguage);
            $translationsJson = htmlspecialchars(json_encode($translations), ENT_QUOTES, 'UTF-8');

            // Gera nonce de uso único para autenticar a chamada à api.php pública
            $nonce = bin2hex(random_bytes(16));
            $_SESSION['lkn_hn_reset_nonce'] = $nonce;

            $frontEndScriptUrl = htmlspecialchars(
                moduleUrl() . '/src/Core/WHMCS/SafePasswordReset/safe_password_reset.js',
                ENT_QUOTES,
                'UTF-8'
            );

            echo "<script
            async
            fetchpriority='high'
            referrerpolicy='origin'
            type='text/javascript'
            src='{$frontEndScriptUrl}'
            data-translations='{$translationsJson}'
            data-api-nonce='{$nonce}'>
        </script>";
        } catch (Throwable $th) {
            lkn_hn_log(
                'handleClientAreaPasswordReset exception',
                ['error' => $th->__toString()]
            );
        }
    }
}
