<?php

use Lkn\HookNotification\Core\AdminUI\Infrastructure\AdminUIRenderer;
use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Settings;
use Lkn\HookNotification\Core\Shared\Infrastructure\Setup\DatabaseSetup;
use Lkn\HookNotification\Core\Shared\Infrastructure\Setup\DatabaseUpgrade;
use WHMCS\Database\Capsule;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Core/Shared/Infrastructure/helpers.php';

/**
 * @since 1.0.0View.php
 *
 * @return array
 */
function lknhooknotification_config()
{
    $language = Capsule::table('tblconfiguration')->where('setting', 'Language')->first('value')->value;

    if (!in_array($language, ['english', 'portuguese-br', 'portuguese-pt'], true)) {
        $language = 'english';
    }

    $version = '4.6.0'; // CHANGE MANUALLY ON RELEASE

    return [
        'name' => lkn_hn_lang('WhatsApp and Chatwoot'),
        'description' => '<div style="margin-bottom: 10px">'.lkn_hn_lang('Send notifications to your customers through WhatsApp or Chatwoot.') . '</div>
        <div>Por <a href="https://www.amssoft.com.br/" target="_blank"><strong>AMS SOFT</strong></a></div>',
        'author' => 'AMS SOFT',
        'authorurl' => 'https://www.amssoft.com.br',
        'language' => $language,
        'version' => $version,
        'fields' => [
            'header' => [
                'Description' => '<div style="margin: 30px;">
                    <div>
                        <a href="addonmodules.php?module=lknhooknotification">
                            <strong>' . lkn_hn_lang('Access Module Settings') . '</strong>
                        </a> &#x2022
                        <a href="logs/module-log">
                            <strong>' . lkn_hn_lang('Access Module Logs') . '</strong>
                        </a>
                    </div>
                    <p style="margin-top: 12px">
                        <i class="fas fa-exclamation-triangle fa-sm"></i>
                        ' . lkn_hn_lang('Grant Access Control to your group to access the module settings page.') . '
                    </p>
                    <p style="margin-top: 12px">
                        <i class="fas fa-exclamation-triangle fa-sm"></i>
                        ' . lkn_hn_lang('If you encounter activation issues due to database tables, make sure that the tblclients table is using the InnoDB engine with the utf8mb4_unicode_ci collation.<br>We recommend backing up the tblclients table before making any changes.') . '
                    </p>
                </div>',
            ],
        ],
    ];
}

/**
 * @since 2.0.0
 *
 * @param array $vars
 *
 * @see https://developers.whmcs.com/addon-modules/upgrades/
 *
 * @return void
 */
function lknhooknotification_upgrade($vars): void
{
    $currentlyInstalledVersion = $vars['version'];

    lkn_hn_config_set(Platforms::MODULE, Settings::MODULE_PREVIOUS_VERSION, $currentlyInstalledVersion);

    if (!$currentlyInstalledVersion) {
        return;
    }

    if ($currentlyInstalledVersion < 2.0) {
        DatabaseUpgrade::v200();
    }

    if ($currentlyInstalledVersion < 2.3) {
        DatabaseUpgrade::v230();
    }

    if ($currentlyInstalledVersion < 3.1) {
        DatabaseUpgrade::v310();
    }

    if ($currentlyInstalledVersion < 3.2) {
        DatabaseUpgrade::v320();
    }

    if ($currentlyInstalledVersion < 3.3) {
        DatabaseUpgrade::v330();
    }

    if ($currentlyInstalledVersion < 3.7) {
        DatabaseUpgrade::v370();
    }

    if ($currentlyInstalledVersion < 3.8) {
        DatabaseUpgrade::v380();
    }

    if (version_compare($currentlyInstalledVersion, '3.9.0', '<')) {
        DatabaseUpgrade::v390();
    }

    if (version_compare($currentlyInstalledVersion, '4.0.0', '<')) {
        DatabaseUpgrade::v400();
    }

    if (version_compare($currentlyInstalledVersion, '4.1.2', '<')) {
        DatabaseUpgrade::v412();
    }

    if (version_compare($currentlyInstalledVersion, '4.3.0', '<')) {
        DatabaseUpgrade::v430();
    }

    if (version_compare($currentlyInstalledVersion, '4.5.0', '<')) {
        DatabaseUpgrade::v450();
    }

    if (version_compare($currentlyInstalledVersion, '4.6.0', '<')) {
        DatabaseUpgrade::v460();
    }

    (new Smarty())->clearAllCache();
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
}

/**
 * @since 1.0.0
 * @see https://developers.whmcs.com/addon-modules/installation-uninstallation/
 *
 * @return array
 */
function lknhooknotification_activate(): array
{
    $response = DatabaseSetup::activate();

    if ($response['status'] === 'success') {
        lkn_hn_config_set(Platforms::MODULE, Settings::DISMISS_INSTALLATION_WELCOME, false);
    }

    return $response;
}

/**
 * @since 1.0.0
 * @see https://developers.whmcs.com/addon-modules/admin-area-output/
 *
 * @param array $vars
 *
 * @return void
 */
function lknhooknotification_output(array $vars): void
{
    try {
        $receivedRoute = isset($_REQUEST['page']) ? strip_tags($_REQUEST['page']) : 'home';

        echo (new AdminUIRenderer())->getView($receivedRoute);
    } catch (Throwable $th) {
        $msg = 'Internal error';

        $error = $th->__toString();

        echo "
        <style>
            #lkn-hn-alert {
                margin: 0px;
                margin-top: 10px;
                margin-bottom: 30px;
            }

            #lkn-hn-alert pre {
                margin: 20px 0px;
                background-color: transparent;
                border-color: #00000014;
            }
        </style>

        <div
        id='lkn-hn-alert'
        class='alert alert-danger alert-dismissible'
        role='alert'
        style='margin: 0px; margin-top: 10px; margin-bottom: 30px;'
    >
            <div
                id='lkn-hn-alert'
                class='alert alert-danger alert-dismissible'
                role='alert'
            >
                <i class='fas fa-exclamation-square'></i>
                {$msg}
                <pre>{$error}</pre>
            </div>
    </div>
        ";
    }
}
