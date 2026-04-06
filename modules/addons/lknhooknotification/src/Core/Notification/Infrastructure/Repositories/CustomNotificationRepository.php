<?php

namespace Lkn\HookNotification\Core\Notification\Infrastructure\Repositories;

use Lkn\HookNotification\Core\Shared\Infrastructure\Repository\BaseRepository;

/**
 * Repositório para notificações criadas dinamicamente pelo painel administrativo.
 *
 * Gerencia a tabela mod_lkn_hook_notification_custom_notifs, que armazena
 * a estrutura (hook, receita base, label) das notificações criadas pelo usuário.
 *
 * @since 4.5.0
 */
class CustomNotificationRepository extends BaseRepository
{
    private const TABLE = 'mod_lkn_hook_notification_custom_notifs';

    /** Retorna todas as notificações customizadas cadastradas */
    public function findAll(): array
    {
        return $this->query->table(self::TABLE)->get()->toArray();
    }

    /** Busca uma notificação customizada pelo seu código único */
    public function findByCode(string $code): ?object
    {
        return $this->query->table(self::TABLE)->where('code', $code)->first() ?: null;
    }

    /** Persiste uma nova notificação customizada */
    public function create(array $data): bool
    {
        return (bool) $this->query->table(self::TABLE)->insert($data);
    }

    /** Atualiza os dados de uma notificação customizada existente */
    public function update(string $code, array $data): bool
    {
        return (bool) $this->query->table(self::TABLE)->where('code', $code)->update($data);
    }

    /** Remove uma notificação customizada pelo código */
    public function delete(string $code): bool
    {
        return (bool) $this->query->table(self::TABLE)->where('code', $code)->delete();
    }

    /** Verifica se já existe uma notificação com o código informado */
    public function existsByCode(string $code): bool
    {
        return $this->query->table(self::TABLE)->where('code', $code)->exists();
    }
}
