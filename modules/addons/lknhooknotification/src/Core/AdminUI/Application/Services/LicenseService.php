<?php

namespace Lkn\HookNotification\Core\AdminUI\Application\Services;

use Lkn\HookNotification\Core\Shared\Infrastructure\Result;
use Lkn\HookNotification\Core\Shared\Infrastructure\Singleton;

final class LicenseService extends Singleton
{
    public function hasLicense(): bool
    {
        return true;
    }

    public function mustBlockNotificationEdit(): bool
    {
        return false;
    }

    public function mustBlockNotificationSending(): bool
    {
        return false;
    }

    public function mustBlockProFeatures(): bool
    {
        return false;
    }

    public function isLicenseActive(): Result
    {
        return lkn_hn_result(code: 'yes');
    }
}
