<?php

namespace Lkn\HookNotification\Core\Notification\Domain;

use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Lkn\HookNotification\Core\Shared\Infrastructure\Hooks;

/**
 * Representa uma notificação criada dinamicamente pelo painel administrativo.
 *
 * Ao contrário das notificações baseadas em arquivo PHP, esta classe é instanciada
 * a partir de dados armazenados no banco de dados, permitindo que administradores
 * sem conhecimento técnico criem novas notificações pelo painel.
 *
 * @since 4.5.0
 */
final class DynamicNotification extends AbstractNotification
{
    /** Indica que esta notificação foi criada pelo painel, não por arquivo PHP */
    public readonly bool $isDynamic;

    public function __construct(
        string $code,
        ?NotificationReportCategory $category,
        ?Hooks $hook,
        NotificationParameterCollection $parameters,
        $findClientId,
        $findCategoryId = null,
        ?string $description = null,
        public readonly ?string $label = null,
    ) {
        parent::__construct($code, $category, $hook, $parameters, $findClientId, $findCategoryId, $description);
        $this->isDynamic = true;
    }
}
