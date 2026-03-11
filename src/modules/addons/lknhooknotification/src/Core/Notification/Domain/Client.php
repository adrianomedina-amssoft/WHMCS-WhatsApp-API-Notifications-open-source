<?php

namespace Lkn\HookNotification\Core\Notification\Domain;

use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Settings;
use Lkn\HookNotification\Core\Shared\Infrastructure\Repository\ClientRepository;
use Lkn\HookNotification\Core\Shared\Validators\PhoneNumberValidator;
use Throwable;
use WHMCS\Database\Capsule;

final class Client
{
    // FIX: Changed properties to accept strings
    public ?string $wpPhoneNumber = null;
    public readonly ?string $whmcsPhoneNumber;
    private readonly ClientRepository $clientRepository;
    public readonly string $locale;

    /**
     * Is null when the client is not registered.
     *
     * @var string|null
     */
    public readonly ?string $countryCode;

    public function __construct(
        public readonly int $id,
    ) {
        $this->clientRepository = new ClientRepository();

        $whmcsPhoneNumber       = $this->clientRepository->getWhmcsPhoneNumber($this->id);
        // FIX: Removed (int) cast. Kept as string after regex.
        $this->whmcsPhoneNumber = $whmcsPhoneNumber ? preg_replace('/[^0-9+]/', '', $whmcsPhoneNumber) : null;
        
        $countryCode            = $this->clientRepository->getClientCountry($this->id);

        $this->countryCode = $countryCode;
        $this->locale      = $this->clientRepository->getClientLang($this->id)['locale'];
    }

    // FIX: Return type is now false|string
    public function validateWpPhoneNumber(int $customFieldId): false|string
    {
        // FIX: Removed (int) cast when fetching the field. Keep it as string.
        $wpPhoneNumberRaw = $this->clientRepository->getCustomField($this->id, $customFieldId);
        
        // FIX: Removed (int) cast.
        $wpPhoneNumber = $wpPhoneNumberRaw ? preg_replace('/[^0-9+]/', '', strval($wpPhoneNumberRaw)) : null;

        if (!$wpPhoneNumber) {
            return false;
        }

        if (!$this->validatePhoneNumber($wpPhoneNumber)) {
            return false;
        }

        $this->wpPhoneNumber = $wpPhoneNumber;
        return $this->wpPhoneNumber;
    }

    // FIX: Return type is now false|string
    public function validateWhmcsPhoneNumber(): false|string
    {
        if (
            empty($this->whmcsPhoneNumber)
            || !$this->validatePhoneNumber($this->whmcsPhoneNumber)
        ) {
            return false;
        }

        return $this->whmcsPhoneNumber;
    }

    // FIX: Return type is now false|string
    public function getWpPhoneNumberOrWhmcsPhoneNumber(?int $platformSpecificWpCustomFieldId): false|string
    {
        /** @var null|int $globalWpCustomFieldId */
        $globalWpCustomFieldId = lkn_hn_config(Settings::WP_CUSTOM_FIELD_ID);

        if ($platformSpecificWpCustomFieldId && !$globalWpCustomFieldId) {
            return $this->validateWpPhoneNumber($platformSpecificWpCustomFieldId);
        }

        if ($globalWpCustomFieldId) {
            return $this->validateWpPhoneNumber($globalWpCustomFieldId);
        }

        return $this->validateWhmcsPhoneNumber();
    }

    /**
     * Valides the phone number against the client country.
     *
     * @param  string $phoneNumber // FIX: Accepts string instead of int
     *
     * @return boolean
     */
    private function validatePhoneNumber(string $phoneNumber): bool
    {
        try {
            if (empty($phoneNumber)) {
                return false;
            }

            if (!PhoneNumberValidator::getInstance()->isValid($phoneNumber, $this->countryCode)) {
                return false;
            }

            return true;
        } catch (Throwable $th) {
            lkn_hn_log(
                'Validate client phone number',
                ['phoneNumber' => $phoneNumber],
                ['exception' => $th->__toString()]
            );

            return false;
        }
    }

    /**
     * @param  integer|null $clientId
     * @param  integer      $customFieldId
     *
     * @return string
     */
    public function getCustomField(?int $clientId, int $customFieldId): string
    {
        $query = Capsule::table('tblcustomfieldsvalues');

        if (!is_null($clientId) && $clientId !== 0) {
            $query = $query->where('relid', $clientId);
        }

        $customFieldValue = $query = $query->where('fieldid', $customFieldId)
            ->first('value')
            ->value;

        return $customFieldValue;
    }
}
