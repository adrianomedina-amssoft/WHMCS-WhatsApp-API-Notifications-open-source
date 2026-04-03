<?php

namespace Lkn\HookNotification\Core\Notification\Http\Controllers;

use Lkn\HookNotification\Core\AdminUI\Application\Services\LicenseService;
use Lkn\HookNotification\Core\Notification\Application\Services\CustomNotificationService;
use Lkn\HookNotification\Core\Notification\Application\Services\NotificationService;
use Lkn\HookNotification\Core\Notification\Application\Services\NotificationViewService;
use Lkn\HookNotification\Core\Notification\Domain\NotificationTemplate;
use Lkn\HookNotification\Core\Platforms\Common\Application\PlatformService;
use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Lkn\HookNotification\Core\Shared\Infrastructure\Hooks;
use Lkn\HookNotification\Core\Shared\Infrastructure\Interfaces\BaseController;
use Lkn\HookNotification\Core\Shared\Infrastructure\View\View;

final class NotificationController extends BaseController
{
    private NotificationService $notificationService;
    private NotificationViewService $notificationViewService;
    private CustomNotificationService $customNotificationService;
    private PlatformService $platformService;

    public function __construct(View $view)
    {
        parent::__construct($view);

        $this->notificationService        = new NotificationService();
        $this->customNotificationService  = new CustomNotificationService();
        $this->platformService            = new PlatformService();
        $this->notificationViewService    = new NotificationViewService($this->view);
    }

    public function viewNotification(
        string $notificationCode,
        string $editingLocale,
        array $request
    ): void {
        $editingNotification = $this->notificationService->buildNotification($notificationCode);

        if (!$editingNotification) {
            lkn_hn_redirect_to_404();

            return;
        }

        $viewParams = [
            'editing_notification' => $editingNotification,
            'platform_list' => $this->platformService->getEnabledPlatforms(),
        ];

        if ($editingLocale === 'new') {
            if (!empty($request['locale'])) {
                header(
                    'Location: addonmodules.php?module=lknhooknotification&page=notifications/'
                    . $editingNotification->code
                    . '/templates/'
                    . $request['locale']
                );

                return;
            }

            $this->view->view(
                'create_edit_notification',
                [
                    ...$viewParams,
                    'request_locale_selection' => true,
                ]
            );

            return;
        } elseif (
                !empty($request['locale']) &&
                $request['locale'] !== $editingLocale
            ) {
            header(
                'Location: addonmodules.php?module=lknhooknotification&page=notifications/'
                . $editingNotification->code
                . '/templates/'
                . $request['locale']
            );

            return;
        } elseif ($editingLocale === 'first' && $editingNotification) {
            if (count($editingNotification->templates) === 0) {
                lkn_hn_redirect_to_404();

                return;
            }

            $firstTemplateLang = $editingNotification->templates[0]->lang;

            header(
                'Location: addonmodules.php?module=lknhooknotification&page=notifications/'
                . $editingNotification->code
                . '/templates/'
                . $firstTemplateLang
            );

            return;
        }

        $foundExistingTemplate = $this->notificationViewService->findTemplateByLang(
            $editingNotification,
            $editingLocale
        );

        if (
            !empty($request) &&
            (
                (
                    // For WhatsApp, requires selecting the message-template before saving.
                    $request['platform'] === 'wp' && (
                        !empty($request['message-template'])
                        || $foundExistingTemplate && $foundExistingTemplate->platform !== $request['platform']
                    )
                )
                ||
                (
                    // For other platforms, requires just selecting the platform. Template is just a textarea.
                    $request['platform'] !== 'wp'
                )
            )
        ) {
            $templateUpdateResult = $this->notificationService->handleUpdate($notificationCode, $request);


            if ($templateUpdateResult->code === 'success') {
                $this->view->alert(
                    'success',
                    lkn_hn_lang('The notification was saved.')
                );

                $editingNotification = $this->notificationService->buildNotification($notificationCode);
            } else {
                $this->view->alert(
                    'danger',
                    lkn_hn_lang(
                        'An error ocurred. The template was not updated. [1]. Go to the module logs for more information.',
                        ['<pre>' . $templateUpdateResult->errors['exception'] . '</pre>']
                    )
                );
            }
        }

        $foundExistingTemplate = $this->notificationViewService->findTemplateByLang(
            $editingNotification,
            $editingLocale
        );

        $editingTemplate = null;

        if (!$foundExistingTemplate) {
            $platform = Platforms::tryFrom($request['platform']);

            $editingTemplate = new NotificationTemplate(
                $platform,
                $editingLocale,
                '',
                []
            );
        } else {
            $editingTemplate = $foundExistingTemplate;
        }

        if (!$editingTemplate->platform) {
            $this->view->view(
                'create_edit_notification',
                [
                    ...$viewParams,
                    'request_platform_selection' => true,
                    'editing_locale' => $editingLocale,
                ]
            );

            return;
        }

        $this->view->view(
            'create_edit_notification',
            [
                ...$viewParams,
                'editing_locale' => $editingLocale,
                'editing_template' => $editingTemplate,
                'editing_notification' => $editingNotification,
                'template_editor_view' => $this->notificationViewService->getTemplateEditorForPlatform(
                    $editingNotification,
                    $editingTemplate
                ),
            ]
        );
    }

    /**
     * @param  array<mixed> $request
     *
     * @return void
     */
    public function viewNotificationsTable(array $request): void
    {
        /** @var string $notificationCode  */
        $notificationCode = $request['notification-code'];
        /** @var string $templateLocale  */
        $templateLocale = $request['template-locale'];

        if (isset($request['delete-template'])) {
            $templateDeletionResult = $this->notificationService->handleTemplateDelete(
                $notificationCode,
                $templateLocale,
            );

            $alert = [
                'type' => $templateDeletionResult->code === 'success' ? 'success' : 'danger',
                'msg' => $templateDeletionResult->code === 'success'
                    ? lkn_hn_lang('The template was deleted.')
                    : lkn_hn_lang(
                        'An error ocurred. The template was not deleted. [1]. Go to the module logs for more information.',
                        ['<pre>' . $templateDeletionResult->errors['exception'] . '</pre>']
                    ),
            ];

            $this->view->alert(...$alert);
        }

        $notifications = $this->notificationService->getNotificationsForView();

        $this->view->view('notifications_table', [
            'notifications' => $notifications,
            'must_block_add_other_notifications' => LicenseService::getInstance()->mustBlockNotificationEdit(),
            'must_block_edit_notification' => LicenseService::getInstance()->mustBlockNotificationEdit(),
            'platform_list' => $this->platformService->getEnabledPlatforms(),
        ]);
    }

    /**
     * Exibe o formulário de criação de notificação customizada.
     * No POST, persiste a notificação e redireciona para o editor de template.
     *
     * @param array<mixed> $request
     */
    public function viewCreateCustomNotification(array $request): void
    {
        if (!empty($request) && isset($request['label'])) {
            $result = $this->customNotificationService->create($request);

            if ($result->code === 'success') {
                $newCode = $result->data['code'];

                // Se o template já foi salvo no formulário, vai para a listagem
                // Caso contrário, redireciona para o editor de template
                if (!empty($result->data['has_template'])) {
                    header('Location: addonmodules.php?module=lknhooknotification&page=notifications&created=1');
                } else {
                    header(
                        'Location: addonmodules.php?module=lknhooknotification&page=notifications/'
                        . $newCode
                        . '/templates/new'
                    );
                }

                return;
            }

            $this->view->alert('danger', $result->errors['message'] ?? $result->errors['exception'] ?? 'Erro ao criar notificação.');
        }

        // Monta lista de hooks agrupados por categoria para o dropdown
        $hookGroups = $this->buildHookGroups();

        // Receitas base disponíveis
        $recipes = [
            'invoice' => lkn_hn_lang('Invoice'),
            'order'   => lkn_hn_lang('Order'),
            'ticket'  => lkn_hn_lang('Ticket'),
            'module'  => lkn_hn_lang('Service'),
            'domain'  => lkn_hn_lang('Domain'),
        ];

        $this->view->view('create_edit_custom_notification', [
            'hook_groups' => $hookGroups,
            'recipes'     => $recipes,
            'form_action' => 'create',
        ]);
    }

    /**
     * Exibe o formulário de edição de uma notificação customizada.
     * No POST, persiste as alterações e reexibe o formulário com feedback.
     *
     * @param array<mixed> $request
     */
    public function viewEditCustomNotification(string $notifCode, array $request): void
    {
        $notification = $this->notificationService->buildNotification($notifCode);

        // Somente notificações criadas pelo painel podem ser editadas por aqui
        if (!$notification || !property_exists($notification, 'isDynamic') || !$notification->isDynamic) {
            lkn_hn_redirect_to_404();

            return;
        }

        if (!empty($request) && isset($request['label'])) {
            $result = $this->customNotificationService->update($notifCode, $request);

            if ($result->code === 'success') {
                $this->view->alert('success', lkn_hn_lang('The notification was saved.'));
                // Recarrega para refletir os dados atualizados
                $notification = $this->notificationService->buildNotification($notifCode);
            } else {
                $this->view->alert('danger', $result->errors['message'] ?? $result->errors['exception'] ?? 'Erro ao salvar.');
            }
        }

        // Busca o registro raw para preencher o formulário com todos os campos
        $rawRecord = $this->customNotificationService->findByCode($notifCode);

        $hookGroups = $this->buildHookGroups();

        $recipes = [
            'invoice' => lkn_hn_lang('Invoice'),
            'order'   => lkn_hn_lang('Order'),
            'ticket'  => lkn_hn_lang('Ticket'),
            'module'  => lkn_hn_lang('Service'),
            'domain'  => lkn_hn_lang('Domain'),
        ];

        $this->view->view('create_edit_custom_notification', [
            'hook_groups'  => $hookGroups,
            'recipes'      => $recipes,
            'form_action'  => 'edit',
            'notif_code'   => $notifCode,
            'editing_data' => $rawRecord,
        ]);
    }

    /**
     * Exclui uma notificação customizada (somente notificações criadas pelo painel).
     *
     * @param array<mixed> $request
     */
    public function handleDeleteCustomNotification(array $request): void
    {
        $code = $request['notification-code'] ?? '';

        $result = $this->customNotificationService->delete($code);

        if ($result->code === 'success') {
            $this->view->alert('success', lkn_hn_lang('Notification deleted successfully.'));
        } else {
            $this->view->alert('danger', $result->errors['message'] ?? $result->errors['exception'] ?? 'Erro ao excluir notificação.');
        }

        $notifications = $this->notificationService->getNotificationsForView();

        $this->view->view('notifications_table', [
            'notifications' => $notifications,
            'must_block_add_other_notifications' => LicenseService::getInstance()->mustBlockNotificationEdit(),
            'must_block_edit_notification' => LicenseService::getInstance()->mustBlockNotificationEdit(),
            'platform_list' => $this->platformService->getEnabledPlatforms(),
        ]);
    }

    /**
     * Clona qualquer notificação (nativa ou customizada) e redireciona para a lista.
     *
     * @param array<mixed> $request
     */
    public function handleCloneNotification(array $request): void
    {
        $sourceCode = $request['source-code'] ?? '';

        $result = $this->customNotificationService->clone($sourceCode);

        if ($result->code === 'success') {
            $newCode = $result->data['code'];

            header(
                'Location: addonmodules.php?module=lknhooknotification&page=notifications/'
                . $newCode
                . '/templates/new&cloned=1'
            );

            return;
        }

        $this->view->alert('danger', $result->errors['message'] ?? $result->errors['exception'] ?? 'Erro ao clonar notificação.');

        $notifications = $this->notificationService->getNotificationsForView();

        $this->view->view('notifications_table', [
            'notifications' => $notifications,
            'must_block_add_other_notifications' => LicenseService::getInstance()->mustBlockNotificationEdit(),
            'must_block_edit_notification' => LicenseService::getInstance()->mustBlockNotificationEdit(),
            'platform_list' => $this->platformService->getEnabledPlatforms(),
        ]);
    }

    /**
     * Retorna lista curada de hooks WHMCS relevantes para o no-code builder,
     * agrupados por categoria com nome e descrição em pt-BR (via lkn_hn_lang).
     *
     * @return array<string, array<string, string>>
     */
    private function buildHookGroups(): array
    {
        return [
            lkn_hn_lang('Invoice') => [
                'InvoiceCreated'       => lkn_hn_lang('hook_InvoiceCreated'),
                'InvoicePaid'          => lkn_hn_lang('hook_InvoicePaid'),
                'InvoiceUnpaid'        => lkn_hn_lang('hook_InvoiceUnpaid'),
                'InvoiceCancelled'     => lkn_hn_lang('hook_InvoiceCancelled'),
                'InvoiceSplit'         => lkn_hn_lang('hook_InvoiceSplit'),
                'InvoiceChangeGateway' => lkn_hn_lang('hook_InvoiceChangeGateway'),
            ],
            lkn_hn_lang('Order') => [
                'OrderPaid'                 => lkn_hn_lang('hook_OrderPaid'),
                'AfterShoppingCartCheckout' => lkn_hn_lang('hook_AfterShoppingCartCheckout'),
                'CancelOrder'               => lkn_hn_lang('hook_CancelOrder'),
            ],
            lkn_hn_lang('Ticket') => [
                'TicketOpen'        => lkn_hn_lang('hook_TicketOpen'),
                'TicketAdminReply'  => lkn_hn_lang('hook_TicketAdminReply'),
                'TicketClose'       => lkn_hn_lang('hook_TicketClose'),
            ],
            lkn_hn_lang('Service') => [
                'AfterModuleCreate'    => lkn_hn_lang('hook_AfterModuleCreate'),
                'AfterModuleSuspend'   => lkn_hn_lang('hook_AfterModuleSuspend'),
                'AfterModuleUnsuspend' => lkn_hn_lang('hook_AfterModuleUnsuspend'),
                'AfterModuleTerminate' => lkn_hn_lang('hook_AfterModuleTerminate'),
            ],
            lkn_hn_lang('Domain') => [
                'DomainTransferCompleted' => lkn_hn_lang('hook_DomainTransferCompleted'),
            ],
            lkn_hn_lang('Client') => [
                'ClientAdd'  => lkn_hn_lang('hook_ClientAdd'),
                'ClientEdit' => lkn_hn_lang('hook_ClientEdit'),
            ],
            lkn_hn_lang('Scheduled (Cron)') => [
                'DailyCronJob' => lkn_hn_lang('hook_DailyCronJob'),
            ],
        ];
    }
}
