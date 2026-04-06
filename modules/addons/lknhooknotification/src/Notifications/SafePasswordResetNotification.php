<?php

namespace Lkn\HookNotification\Notifications\Custom;

use Lkn\HookNotification\Core\Notification\Domain\AbstractNotification;
use Lkn\HookNotification\Core\Notification\Domain\NotificationParameter;
use Lkn\HookNotification\Core\Notification\Domain\NotificationParameterCollection;

final class SafePasswordResetNotification extends AbstractNotification
{
    public function __construct()
    {
        parent::__construct(
            'SafePasswordReset',
            null,
            null,
            new NotificationParameterCollection([
                new NotificationParameter(
                    'password_reset_url',
                    lkn_hn_lang('Password reset URL'),
                    fn (): string => get_passsword_reset_url_for_user($this->whmcsHookParams['client_user_owner_email'])
                ),
                new NotificationParameter(
                    'password_reset_token',
                    lkn_hn_lang('Password reset token'),
                    fn (): string => get_user_password_reset_token_by_user_email($this->whmcsHookParams['client_user_owner_email'])
                ),
                new NotificationParameter(
                    'client_id',
                    lkn_hn_lang('Client ID'),
                    fn (): int => $this->client->id
                ),
                new NotificationParameter(
                    'client_email',
                    lkn_hn_lang('Client email'),
                    fn (): string => getClientEmailByClientId($this->client->id)
                ),
                new NotificationParameter(
                    'client_first_name',
                    lkn_hn_lang('Client first name'),
                    fn (): string => getClientFirstNameByClientId($this->client->id)
                ),
                new NotificationParameter(
                    'client_full_name',
                    lkn_hn_lang('Client full name'),
                    fn (): string => getClientFullNameByClientId($this->client->id)
                ),
            ]),
            fn() => $this->whmcsHookParams['client_id'],
            description: 'This notification also affect the password reset page by using the existing recovery token in case its not expired yet. It avois sending multiple emails with different recovery tokens. The notification pass reset_password_url on the Password Reset Validation email template. (Tested on Twenty One theme)',
        );
    }
}
