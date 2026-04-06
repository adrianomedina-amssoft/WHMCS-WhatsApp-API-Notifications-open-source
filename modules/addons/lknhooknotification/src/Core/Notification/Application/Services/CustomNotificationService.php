<?php

namespace Lkn\HookNotification\Core\Notification\Application\Services;

use Exception;
use Lkn\HookNotification\Core\Notification\Infrastructure\Repositories\CustomNotificationRepository;
use Lkn\HookNotification\Core\Notification\Infrastructure\Repositories\NotificationRepository;
use Lkn\HookNotification\Core\Notification\Application\NotificationFactory;
use Lkn\HookNotification\Core\Shared\Infrastructure\Hooks;
use Lkn\HookNotification\Core\Shared\Infrastructure\Result;

/**
 * Serviço responsável por criar, editar, excluir e clonar notificações
 * criadas dinamicamente pelo painel administrativo.
 *
 * @since 4.5.0
 */
final class CustomNotificationService
{
    private CustomNotificationRepository $customNotifRepo;
    private NotificationRepository $notifRepo;
    private NotificationFactory $factory;

    public function __construct()
    {
        $this->customNotifRepo = new CustomNotificationRepository();
        $this->notifRepo       = new NotificationRepository();
        $this->factory         = NotificationFactory::getInstance();
    }

    /** Retorna todas as notificações customizadas cadastradas */
    public function getAll(): array
    {
        return $this->customNotifRepo->findAll();
    }

    /** Retorna o registro raw de uma notificação customizada pelo código */
    public function findByCode(string $code): ?object
    {
        return $this->customNotifRepo->findByCode($code);
    }

    /**
     * Cria uma nova notificação customizada.
     *
     * @param array{label: string, code: string, hook: string, base_recipe: string, description?: string} $data
     */
    public function create(array $data): Result
    {
        try {
            $code = trim($data['code'] ?? '');
            $label = trim($data['label'] ?? '');
            $hook = trim($data['hook'] ?? '');
            $baseRecipe = trim($data['base_recipe'] ?? '');

            // Validações básicas de campos obrigatórios
            if (empty($code) || empty($label) || empty($hook) || empty($baseRecipe)) {
                return lkn_hn_result('error', errors: ['message' => 'Campos obrigatórios: code, label, hook, base_recipe.']);
            }

            // Código deve ser alfanumérico com underscores
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $code)) {
                return lkn_hn_result('error', errors: ['message' => 'O código deve começar com letra e conter apenas letras, números e underscores.']);
            }

            // Valida se o hook existe no enum
            if (!Hooks::tryFrom($hook)) {
                return lkn_hn_result('error', errors: ['message' => "Hook inválido: {$hook}"]);
            }

            // Verifica unicidade do código (tabela custom e tabela de templates)
            if ($this->customNotifRepo->existsByCode($code)) {
                return lkn_hn_result('error', errors: ['message' => "Já existe uma notificação com o código: {$code}"]);
            }

            // Dias de atraso: somente para hooks do tipo DailyCronJob
            $days = null;
            if ($hook === 'DailyCronJob' && !empty($data['days'])) {
                $days = (int) $data['days'];
            }

            $isActive = isset($data['is_active']) ? 1 : 0;

            $this->customNotifRepo->create([
                'code'           => $code,
                'label'          => $label,
                'description'    => trim($data['description'] ?? ''),
                'hook'           => $hook,
                'base_recipe'    => $baseRecipe,
                'days'           => $days,
                'condition_note' => trim($data['condition_note'] ?? ''),
                'is_active'      => $isActive,
                'cloned_from'    => null,
            ]);

            // Se o usuário forneceu um template inicial, salva imediatamente
            $initialTemplate = trim($data['template'] ?? '');

            if (!empty($initialTemplate)) {
                $locale = trim($data['locale'] ?? 'pt_BR');
                $platform = trim($data['platform'] ?? 'evo');

                $this->notifRepo->createNotificationTemplate(
                    $code,
                    $platform,
                    $locale,
                    $initialTemplate,
                    []
                );
            }

            return lkn_hn_result('success', data: ['code' => $code, 'has_template' => !empty($initialTemplate)]);
        } catch (Exception $e) {
            return lkn_hn_result('error', errors: ['exception' => $e->getMessage()]);
        }
    }

    /**
     * Atualiza os campos editáveis de uma notificação customizada existente.
     * O código (identificador único) e a receita base não podem ser alterados
     * para preservar a compatibilidade com os templates já configurados.
     *
     * @param array{label?: string, description?: string, condition_note?: string, days?: int|null, is_active?: int} $data
     */
    public function update(string $code, array $data): Result
    {
        try {
            if (!$this->customNotifRepo->existsByCode($code)) {
                return lkn_hn_result('error', errors: ['message' => "Notificação não encontrada: {$code}"]);
            }

            $updateData = [];

            if (!empty($data['label'])) {
                $updateData['label'] = trim($data['label']);
            }

            if (isset($data['description'])) {
                $updateData['description'] = trim($data['description']);
            }

            if (isset($data['condition_note'])) {
                $updateData['condition_note'] = trim($data['condition_note']);
            }

            // Dias: permitido somente para DailyCronJob; null quando não informado
            if (array_key_exists('days', $data)) {
                $updateData['days'] = !empty($data['days']) ? (int) $data['days'] : null;
            }

            $updateData['is_active'] = isset($data['is_active']) ? 1 : 0;

            if (!empty($updateData)) {
                $this->customNotifRepo->update($code, $updateData);
            }

            return lkn_hn_result('success');
        } catch (Exception $e) {
            return lkn_hn_result('error', errors: ['exception' => $e->getMessage()]);
        }
    }

    /**
     * Exclui uma notificação customizada e todos os seus templates.
     * Notificações nativas (arquivo PHP ou built-in) não podem ser excluídas por aqui.
     */
    public function delete(string $code): Result
    {
        try {
            if (!$this->customNotifRepo->existsByCode($code)) {
                return lkn_hn_result('error', errors: ['message' => "Notificação não encontrada: {$code}"]);
            }

            // Remove todos os templates associados na tabela de templates
            $this->notifRepo->deleteAllTemplatesForNotification($code);

            // Remove o registro da notificação customizada
            $this->customNotifRepo->delete($code);

            return lkn_hn_result('success');
        } catch (Exception $e) {
            return lkn_hn_result('error', errors: ['exception' => $e->getMessage()]);
        }
    }

    /**
     * Clona qualquer notificação (nativa, arquivo PHP ou customizada).
     *
     * Cria uma nova entrada em custom_notifs com base na notificação original,
     * copiando também todos os seus templates de mensagem.
     */
    public function clone(string $sourceCode): Result
    {
        try {
            // Busca a notificação fonte (pode ser qualquer tipo)
            $sourceNotification = $this->factory->makeByCode($sourceCode);

            if (!$sourceNotification) {
                return lkn_hn_result('error', errors: ['message' => "Notificação fonte não encontrada: {$sourceCode}"]);
            }

            // Determina o base_recipe a partir da categoria da notificação fonte
            $baseRecipe = $this->resolveBaseRecipeFromCategory($sourceNotification->category?->value);

            if (!$baseRecipe) {
                return lkn_hn_result('error', errors: ['message' => 'Não foi possível determinar a receita base para esta notificação.']);
            }

            // Gera um código único para o clone
            $newCode = $sourceCode . '_clone_' . time();

            // Obtém o label: se for DynamicNotification usa o label, senão usa o code
            $sourceLabel = property_exists($sourceNotification, 'label') && $sourceNotification->label
                ? $sourceNotification->label
                : $sourceCode;

            $this->customNotifRepo->create([
                'code'        => $newCode,
                'label'       => 'Cópia de ' . $sourceLabel,
                'description' => $sourceNotification->description ?? '',
                'hook'        => $sourceNotification->hook->value,
                'base_recipe' => $baseRecipe,
                'cloned_from' => $sourceCode,
            ]);

            // Copia os templates existentes da notificação original
            foreach ($sourceNotification->templates as $template) {
                $this->notifRepo->createNotificationTemplate(
                    $newCode,
                    $template->platform->value,
                    $template->lang,
                    $template->template,
                    $template->platformPayload ?? []
                );
            }

            return lkn_hn_result('success', data: ['code' => $newCode]);
        } catch (Exception $e) {
            return lkn_hn_result('error', errors: ['exception' => $e->getMessage()]);
        }
    }

    /**
     * Mapeia a categoria da notificação para o nome da receita base correspondente.
     * Retorna null se não houver receita compatível.
     */
    private function resolveBaseRecipeFromCategory(?string $category): ?string
    {
        return match ($category) {
            'invoice' => 'invoice',
            'order'   => 'order',
            'ticket'  => 'ticket',
            'service' => 'module',
            default   => null,
        };
    }
}
