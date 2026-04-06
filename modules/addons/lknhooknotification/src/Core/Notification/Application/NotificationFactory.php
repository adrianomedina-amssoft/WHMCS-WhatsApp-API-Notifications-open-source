<?php

namespace Lkn\HookNotification\Core\Notification\Application;

use Lkn\HookNotification\Core\Notification\Domain\AbstractNotification;
use Lkn\HookNotification\Core\Notification\Domain\BuiltInNotification;
use Lkn\HookNotification\Core\Notification\Domain\DynamicCronNotification;
use Lkn\HookNotification\Core\Notification\Domain\DynamicNotification;
use Lkn\HookNotification\Core\Notification\Domain\NotificationTemplate;
use Lkn\HookNotification\Core\Notification\Infrastructure\Repositories\CustomNotificationRepository;
use Lkn\HookNotification\Core\Notification\Infrastructure\Repositories\NotificationRepository;
use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Lkn\HookNotification\Core\Shared\Infrastructure\Hooks;
use Lkn\HookNotification\Core\Shared\Infrastructure\Singleton;
use Throwable;

/**
 * This class contains the logic for instantiating AbstractNotificaions from files
 * and from built_in_notifications_recipes.php.
 */
final class NotificationFactory extends Singleton
{
    private NotificationRepository $notificationRepository;
    private CustomNotificationRepository $customNotificationRepository;
    private array $builtInNotifRecipes;
    private string $fileNotificationsDir;

    protected function __construct()
    {
        $this->notificationRepository            = new NotificationRepository();
        $this->customNotificationRepository      = new CustomNotificationRepository();
        $this->builtInNotifRecipes               = require __DIR__ . '/built_in_notifications_recipes.php';
        $this->fileNotificationsDir              = __DIR__ . '/../../../Notifications';
        $this->fileBasedNotificationClassesCache = [];
    }

    /**
     * @var string[]
     */
    private array $fileBasedNotificationClassesCache;


    public function makeByCode(string $notificationCode): ?AbstractNotification
    {
        $activeTemplates   = $this->notificationRepository->getEnabledNotifications();
        $rawNotifTemplates = $activeTemplates[$notificationCode] ?? [];

        $fileNotifications = $this->makeFileBasedNotification(
            false,
            [$notificationCode => $rawNotifTemplates],
            $notificationCode
        );

        foreach ($fileNotifications as $notif) {
            if ($notif->code === $notificationCode) {
                return $notif;
            }
        }

        $builtInNotifications = $this->makeBuiltInNotifications(
            false,
            [$notificationCode => $rawNotifTemplates],
            $notificationCode
        );

        foreach ($builtInNotifications as $notif) {
            if ($notif->code === $notificationCode) {
                return $notif;
            }
        }

        // Busca em notificações criadas pelo painel
        $dynamicNotifications = $this->makeDynamicNotifications(
            false,
            [$notificationCode => $rawNotifTemplates],
            $notificationCode
        );

        foreach ($dynamicNotifications as $notif) {
            if ($notif->code === $notificationCode) {
                return $notif;
            }
        }

        return null;
    }

    /**
     * @param  boolean $mergeEnabledTemplates
     *
     * @return AbstractNotification[]
     */
    public function makeAll(bool $mergeEnabledTemplates)
    {
        $activeTemplates = [];

        if ($mergeEnabledTemplates) {
            $activeTemplates = $this->notificationRepository->getEnabledNotifications();
        }

        $fileBasedNotifications = $this->makeFileBasedNotification(
            false,
            $activeTemplates
        );

        $builtInNotifications = $this->makeBuiltInNotifications(
            false,
            $activeTemplates
        );

        return [
            ...$fileBasedNotifications,
            ...$builtInNotifications,
            ...$this->makeDynamicNotifications(false, $activeTemplates),
        ];
    }

    /**
     * @param  Hooks $hook
     *
     * @return AbstractNotification[]
     */
    public function makeAllForHook(Hooks $hook, bool $onlyEnabled = false): array
    {
        $activeTemplates = $this->notificationRepository->getEnabledNotifications();

        $manualFileBasedNotifications = $this->makeFileBasedNotification(
            $onlyEnabled,
            $activeTemplates,
            filterForNotification:
                function (AbstractNotification $notification) use ($hook, $onlyEnabled) {
                    return $notification->hook === $hook;
                }
        );

        // Inclui notificações dinâmicas que correspondem ao hook solicitado
        $dynamicNotifications = array_values(array_filter(
            $this->makeDynamicNotifications($onlyEnabled, $activeTemplates),
            fn(AbstractNotification $n) => $n->hook === $hook
        ));

        return [...$manualFileBasedNotifications, ...$dynamicNotifications];
    }

    /**
     * @return AbstractNotification[]
     */
    public function makeEnabledNotifs(): array
    {
        $activeTemplates = $this->notificationRepository->getEnabledNotifications();

        $activeTemplatesForBuiltIn = array_filter(
            $activeTemplates,
            fn(string $key) => str_starts_with($key, 'Default'),
            ARRAY_FILTER_USE_KEY
        );

        $activeTemplatesForFileBased = array_diff_key($activeTemplates, $activeTemplatesForBuiltIn);

        return [
            ...$this->makeBuiltInNotifications(true, $activeTemplatesForBuiltIn),
            ...$this->makeFileBasedNotification(true, $activeTemplatesForFileBased),
            ...$this->makeDynamicNotifications(true, $activeTemplates),
        ];
    }

    /**
     * @param  boolean    $makeNotificationsWithActiveTemplatesOnly
     * @param  array|null $templatesByNotification
     *
     * @return AbstractNotification[]
     */
    private function makeFileBasedNotification(
        bool $makeNotificationsWithActiveTemplatesOnly = false,
        ?array $templatesByNotification = null,
        ?string $filterByCode = null,
        $filterForNotification = null
    ): array {
        /**
         * Have to cache because if makeFileBasedNotification() is called again,
         * $newClasses will be empty as the all notification classes were loaded.
         */
        if (empty($this->fileBasedNotificationClassesCache)) {
            $before = get_declared_classes();

            $notificationPaths = array_merge(
                glob("{$this->fileNotificationsDir}/*.php") ?: [],
                glob("{$this->fileNotificationsDir}/Custom/*.php") ?: []
            );

            array_map(static fn($file) => require_once $file, $notificationPaths);

            $this->fileBasedNotificationClassesCache = array_filter(
                array_diff(get_declared_classes(), $before),
                static fn($class) => !str_ends_with($class, '\AbstractNotification')
                      && !str_ends_with($class, '\AbstractManualNotification')
                      && !str_ends_with($class, '\AbstractCronNotification')
            );
        }

        $instances = [];

        foreach ($this->fileBasedNotificationClassesCache as $class) {
            try {
                /** @var AbstractNotification $notification */
                $notification = new $class();
            } catch (Throwable $th) {
                lkn_hn_log(
                    'Error instantiating notification',
                    ['class' => $class],
                    ['exception' => $th->__toString()]
                );

                continue;
            }

            if ($filterByCode !== null && $filterByCode !== $notification->code) {
                continue;
            }

            $rawNotifTemplates = $templatesByNotification[$notification->code] ?? [];

            if (
                $makeNotificationsWithActiveTemplatesOnly &&
                empty($rawNotifTemplates)
            ) {
                continue;
            }

            $notification->setTemplates(
                $this->buildNotificationTemplates($rawNotifTemplates)
            );

            if (is_callable($filterForNotification) && !$filterForNotification($notification)) {
                continue;
            }

            $instances[] = $notification;
        }

        return $instances;
    }

    /**
     * @param  boolean     $makeNotificationsWithActiveTemplatesOnly
     * @param  array|null  $templatesByNotification
     * @param  string|null $filterByCode
     * @param  \Closure    $filterForNotification
     *
     * @return AbstractNotification[]
     */
    private function makeBuiltInNotifications(
        bool $makeNotificationsWithActiveTemplatesOnly,
        ?array $templatesByNotification = null,
        ?string $filterByCode = null,
        mixed $filterForNotification = null
    ): array {
        $instances = [];

        foreach ($this->builtInNotifRecipes as $recipe) {
            foreach ($recipe['hooks'] as $hook) {
                $hookAsNotifCode = 'Default' . str_replace('_', '', $hook->name);

                if ($filterByCode !== null && $filterByCode !== $hookAsNotifCode) {
                    continue;
                }

                /** @var NotificationTemplate[] $rawNotifTemplates */
                $rawNotifTemplates = $templatesByNotification[$hookAsNotifCode] ?? [];

                if (
                    $makeNotificationsWithActiveTemplatesOnly &&
                    empty($rawNotifTemplates)
                ) {
                    continue;
                }

                $notifInstance = new BuiltInNotification(
                    $hookAsNotifCode,
                    $recipe['category'],
                    $hook,
                    $recipe['params'],
                    $recipe['clientIdFinder'],
                    $recipe['categoryIdFinder'],
                );

                $notifTemplatesInstances = $this->buildNotificationTemplates($rawNotifTemplates);

                $notifInstance->setTemplates($notifTemplatesInstances);

                if ($filterForNotification && !($filterForNotification)($notifInstance)) {
                    continue;
                }

                $instances[] = $notifInstance;
            }
        }

        return $instances;
    }

    /**
     * Instancia as notificações criadas dinamicamente pelo painel administrativo.
     *
     * Cada registro em mod_lkn_hook_notification_custom_notifs é convertido
     * em um DynamicNotification usando os closures da receita base correspondente.
     *
     * @param  boolean    $onlyEnabled                 Se verdadeiro, retorna apenas notificações com template ativo
     * @param  array|null $templatesByNotification     Mapa de templates ativos indexado por código
     * @param  string|null $filterByCode               Se informado, retorna apenas a notificação com este código
     *
     * @return DynamicNotification[]
     */
    private function makeDynamicNotifications(
        bool $onlyEnabled = false,
        ?array $templatesByNotification = null,
        ?string $filterByCode = null
    ): array {
        // Garante que a tabela existe antes de consultar (instalações antigas sem upgrade)
        try {
            $records = $this->customNotificationRepository->findAll();
        } catch (Throwable $th) {
            return [];
        }

        $instances = [];

        foreach ($records as $record) {
            if ($filterByCode !== null && $filterByCode !== $record->code) {
                continue;
            }

            // A receita base define clientIdFinder, categoryIdFinder e params
            $recipe = $this->builtInNotifRecipes[$record->base_recipe] ?? null;

            if (!$recipe) {
                continue;
            }

            $hook = Hooks::tryFrom($record->hook);

            if (!$hook) {
                continue;
            }

            $rawNotifTemplates = $templatesByNotification[$record->code] ?? [];

            // Respeita o campo is_active: notificações desativadas não disparam mesmo com template
            if ($onlyEnabled && (empty($rawNotifTemplates) || empty($record->is_active))) {
                continue;
            }

            // Notificações com DailyCronJob precisam de getPayload() — usa DynamicCronNotification
            $isCronHook = $hook === Hooks::DAILY_CRON_JOB;

            $notifInstance = $isCronHook
                ? new DynamicCronNotification(
                    $record->code,
                    $recipe['category'],
                    $hook,
                    $recipe['params'],
                    $recipe['clientIdFinder'],
                    $recipe['categoryIdFinder'],
                    $record->description ?: null,
                    $record->label,
                    isset($record->days) ? (int) $record->days : null,
                    $record->base_recipe,
                )
                : new DynamicNotification(
                    $record->code,
                    $recipe['category'],
                    $hook,
                    $recipe['params'],
                    $recipe['clientIdFinder'],
                    $recipe['categoryIdFinder'],
                    $record->description ?: null,
                    $record->label,
                );

            $notifInstance->setTemplates(
                $this->buildNotificationTemplates($rawNotifTemplates)
            );

            $instances[] = $notifInstance;
        }

        return $instances;
    }

    /**
     * @param  NotificationTemplate[] $rawNotifTemplates
     *
     * @return NotificationTemplate[]
     */
    private function buildNotificationTemplates(array $rawNotifTemplates): array
    {
        return array_map(
            function (array $notifTemplate) {
                // Defaults backto WP_EVO because multi-lang for
                // templates was initially implemented for it.
                $platform = Platforms::tryFrom($notifTemplate['platform']) ?? Platforms::WP_EVO;

                $platformPayload = $notifTemplate['platform_payload'] ?? [];

                return new NotificationTemplate(
                    $platform,
                    $notifTemplate['lang'],
                    $notifTemplate['tpl'],
                    $platformPayload
                );
            },
            $rawNotifTemplates
        );
    }
}
