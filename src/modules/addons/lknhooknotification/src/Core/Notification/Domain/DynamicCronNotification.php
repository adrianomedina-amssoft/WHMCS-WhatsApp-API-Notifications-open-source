<?php

namespace Lkn\HookNotification\Core\Notification\Domain;

use DateTime;
use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Lkn\HookNotification\Core\Shared\Infrastructure\Hooks;
use WHMCS\Database\Capsule;

/**
 * Notificação dinâmica de cron criada pelo painel administrativo.
 *
 * Assim como as notificações de arquivo (ex: Invoice6DaysLateNotification),
 * esta classe estende AbstractCronNotification para que o NotificationSender
 * chame getPayload() e dispare uma mensagem por cliente/fatura/domínio encontrado.
 *
 * O campo `days` armazenado no banco define quantos dias de atraso (fatura)
 * ou dias até o vencimento (domínio) devem ser considerados no filtro.
 *
 * @since 4.5.0
 */
final class DynamicCronNotification extends AbstractCronNotification
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
        private readonly ?int $days = null,
        private readonly string $baseRecipe = 'invoice',
    ) {
        parent::__construct($code, $category, $hook, $parameters, $findClientId, $findCategoryId, $description);
        $this->isDynamic = true;
    }

    /**
     * Retorna os itens (faturas ou domínios) que devem disparar esta notificação hoje.
     * Cada item do array é passado como $whmcsHookParams para finishInit() e send().
     *
     * @return array<array<string, mixed>>
     */
    public function getPayload(): array
    {
        return match ($this->baseRecipe) {
            'invoice' => $this->getOverdueInvoicesPayload(),
            'domain'  => $this->getExpiringDomainsPayload(),
            default   => [],
        };
    }

    /**
     * Busca faturas com status "Overdue" cujo vencimento foi há exatamente $days dias.
     * Replica o padrão de Invoice6DaysLateNotification e Invoice15DaysLateNotification.
     */
    private function getOverdueInvoicesPayload(): array
    {
        if ($this->days === null) {
            return [];
        }

        $invoices = localAPI('GetInvoices', [
            'limitnum' => 1000,
            'status'   => 'Overdue',
        ]);

        if (empty($invoices['invoices']['invoice'])) {
            return [];
        }

        $payloads        = [];
        $currentDateTime = new DateTime();

        foreach ($invoices['invoices']['invoice'] as $invoice) {
            $dueDate  = new DateTime($invoice['duedate']);
            $interval = $currentDateTime->diff($dueDate);

            // Filtra: apenas faturas com exatamente $days dias em atraso
            // e que não sejam freeproducts ou de valor zero
            if (
                (int) $interval->days !== $this->days
                || $invoice['paymentmethod'] === 'freeproducts'
                || $invoice['total'] === '0.00'
            ) {
                continue;
            }

            // Chave `invoiceid` alinha com categoryIdFinder e clientIdFinder da receita invoice
            $payloads[] = [
                'report_category_id' => (int) $invoice['id'],
                'invoiceid'          => (int) $invoice['id'],
            ];
        }

        return $payloads;
    }

    /**
     * Busca domínios ativos cujo vencimento ocorre daqui a exatamente $days dias.
     * Replica o padrão de DomainRenewal5DaysBeforeNotification.
     */
    private function getExpiringDomainsPayload(): array
    {
        if ($this->days === null) {
            return [];
        }

        $targetDate = (new DateTime())->modify("+{$this->days} days")->format('Y-m-d');

        $domains = Capsule::table('tbldomains')
            ->where('status', 'Active')
            ->where('nextduedate', $targetDate)
            ->get(['id', 'userid'])
            ->toArray();

        $payloads = [];

        foreach ($domains as $domain) {
            // Chaves `domain_id` e `client_id` alinham com categoryIdFinder/clientIdFinder da receita domain
            $payloads[] = [
                'report_category_id' => (int) $domain->id,
                'domain_id'          => (int) $domain->id,
                'client_id'          => (int) $domain->userid,
            ];
        }

        return $payloads;
    }
}
