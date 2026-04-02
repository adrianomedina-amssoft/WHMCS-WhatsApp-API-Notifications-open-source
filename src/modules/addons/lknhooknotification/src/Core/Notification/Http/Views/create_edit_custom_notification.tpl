{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {lkn_hn_lang text="New Notification"}
{/block}

{block "title_right_side"}
    <a class="btn btn-link" href="{$lkn_hn_base_endpoint}&page=notifications">
        <i class="fas fa-arrow-left"></i>
        {lkn_hn_lang text="Back to list"}
    </a>
{/block}

{block "page_content"}
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">{lkn_hn_lang text="Notification settings"}</h3>
                </div>
                <div class="panel-body">
                    <form method="POST" action="{$lkn_hn_base_endpoint}&page=notifications/new">

                        {* Nome visível na listagem *}
                        <div class="form-group">
                            <label for="notif-label">
                                {lkn_hn_lang text="Notification name"} <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control"
                                id="notif-label"
                                name="label"
                                placeholder="{lkn_hn_lang text='Ex: Invoice overdue for 3 days'}"
                                required
                            >
                            <p class="help-block">{lkn_hn_lang text="Descriptive name shown in the notification list."}</p>
                        </div>

                        {* Código interno único *}
                        <div class="form-group">
                            <label for="notif-code">
                                {lkn_hn_lang text="Internal code"} <span class="text-danger">*</span>
                                <i
                                    class="far fa-question-circle"
                                    data-toggle="tooltip"
                                    data-placement="right"
                                    title="{lkn_hn_lang text='Unique identifier. Only letters, numbers and underscores. Cannot be changed after creation.'}"
                                ></i>
                            </label>
                            <input
                                type="text"
                                class="form-control"
                                id="notif-code"
                                name="code"
                                placeholder="Ex: InvoiceOverdue3Days"
                                pattern="[A-Za-z][A-Za-z0-9_]*"
                                required
                            >
                        </div>

                        {* Hook do WHMCS *}
                        <div class="form-group">
                            <label for="notif-hook">
                                {lkn_hn_lang text="Hook / Trigger"} <span class="text-danger">*</span>
                                <i
                                    class="far fa-question-circle"
                                    data-toggle="tooltip"
                                    data-placement="right"
                                    title="{lkn_hn_lang text='WHMCS event that triggers this notification.'}"
                                ></i>
                            </label>
                            <select class="form-control" id="notif-hook" name="hook" required>
                                <option value="">{lkn_hn_lang text="Select a hook..."}</option>
                                {foreach from=$page_params.hook_groups key=$groupName item=$hooks}
                                    <optgroup label="{$groupName}">
                                        {foreach from=$hooks key=$hookValue item=$hookLabel}
                                            <option value="{$hookValue}">{$hookLabel}</option>
                                        {/foreach}
                                    </optgroup>
                                {/foreach}
                            </select>
                        </div>

                        {* Campo de dias — visível apenas quando hook = DailyCronJob *}
                        <div class="form-group" id="field-days" style="display:none;">
                            <label for="notif-days">
                                {lkn_hn_lang text="Days of delay"} <span class="text-danger">*</span>
                                <i
                                    class="far fa-question-circle"
                                    data-toggle="tooltip"
                                    data-placement="right"
                                    title="{lkn_hn_lang text='Number of days overdue (invoice) or until expiry (domain) to trigger this notification.'}"
                                ></i>
                            </label>
                            <input
                                type="number"
                                class="form-control"
                                id="notif-days"
                                name="days"
                                min="1"
                                placeholder="{lkn_hn_lang text='Ex: 3'}"
                            >
                            <p class="help-block">{lkn_hn_lang text="Example: 3 = fires when invoice is 3 days overdue."}</p>
                        </div>

                        {* Receita base *}
                        <div class="form-group">
                            <label for="notif-recipe">
                                {lkn_hn_lang text="Base recipe"} <span class="text-danger">*</span>
                                <i
                                    class="far fa-question-circle"
                                    data-toggle="tooltip"
                                    data-placement="right"
                                    title="{lkn_hn_lang text='Determines which variables will be available in the message template (client name, invoice ID, etc.).'}"
                                ></i>
                            </label>
                            <select class="form-control" id="notif-recipe" name="base_recipe" required>
                                <option value="">{lkn_hn_lang text="Select a recipe..."}</option>
                                {foreach from=$page_params.recipes key=$recipeKey item=$recipeLabel}
                                    <option value="{$recipeKey}">{$recipeLabel}</option>
                                {/foreach}
                            </select>
                        </div>

                        {* Condição opcional *}
                        <div class="form-group">
                            <label for="notif-condition">
                                {lkn_hn_lang text="Condition (optional)"}
                                <i
                                    class="far fa-question-circle"
                                    data-toggle="tooltip"
                                    data-placement="right"
                                    title="{lkn_hn_lang text='Optional note about when this notification should fire. Example: only for clients in group X.'}"
                                ></i>
                            </label>
                            <input
                                type="text"
                                class="form-control"
                                id="notif-condition"
                                name="condition_note"
                                placeholder="{lkn_hn_lang text='Ex: Only for invoices above R$100'}"
                            >
                        </div>

                        {* Descrição *}
                        <div class="form-group">
                            <label for="notif-description">{lkn_hn_lang text="Description"}</label>
                            <textarea
                                class="form-control"
                                id="notif-description"
                                name="description"
                                rows="2"
                                placeholder="{lkn_hn_lang text='Briefly describe what this notification does and when it fires.'}"
                            ></textarea>
                        </div>

                        {* Template de mensagem *}
                        <div class="form-group">
                            <label for="notif-template">
                                {lkn_hn_lang text="Message template"}
                                <i
                                    class="far fa-question-circle"
                                    data-toggle="tooltip"
                                    data-placement="right"
                                    title="{lkn_hn_lang text='WhatsApp message. Use the variables shown on the right. You can add more templates later.'}"
                                ></i>
                            </label>
                            <textarea
                                class="form-control"
                                id="notif-template"
                                name="template"
                                rows="5"
                                placeholder="{lkn_hn_lang text='Ex: Hello {ldelim}{ldelim}client_first_name{rdelim}{rdelim}, your invoice #{ldelim}{ldelim}invoice_id{rdelim}{rdelim} is overdue. Pay now: {ldelim}{ldelim}invoice_pdf_url{rdelim}{rdelim}'}"
                            ></textarea>
                            <p class="help-block">
                                {lkn_hn_lang text="Leave blank to configure later. Language:"}
                                <select name="locale" class="input-sm" style="margin-left:4px;">
                                    <option value="pt_BR">pt_BR</option>
                                    <option value="en_001">en_001</option>
                                    <option value="es_ES">es_ES</option>
                                </select>
                                {lkn_hn_lang text="Platform:"}
                                <select name="platform" class="input-sm" style="margin-left:4px;">
                                    <option value="evo">Evolution API</option>
                                    <option value="baileys">Baileys</option>
                                    <option value="mod">Chatwoot</option>
                                </select>
                            </p>
                        </div>

                        {* Status *}
                        <div class="form-group">
                            <label>{lkn_hn_lang text="Status"}</label>
                            <div class="checkbox">
                                <label>
                                    <input
                                        type="checkbox"
                                        name="is_active"
                                        id="notif-status"
                                        value="1"
                                        checked
                                    >
                                    {lkn_hn_lang text="Enabled"}
                                </label>
                            </div>
                            <p class="help-block">{lkn_hn_lang text="Disabled notifications are ignored even if they have configured templates."}</p>
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            {lkn_hn_lang text="Save notification"}
                        </button>
                        <a href="{$lkn_hn_base_endpoint}&page=notifications" class="btn btn-default" style="margin-left: 8px;">
                            {lkn_hn_lang text="Cancel"}
                        </a>
                    </form>
                </div>
            </div>
        </div>

        {* Painel lateral de variáveis disponíveis *}
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fas fa-code"></i>
                        {lkn_hn_lang text="Available variables"}
                    </h3>
                </div>
                <div class="panel-body" style="padding: 10px 15px;">
                    <p class="text-muted" style="font-size:0.82em; margin-bottom: 8px;">
                        {lkn_hn_lang text="Click a variable to insert it at cursor position in the template."}
                    </p>

                    {* Placeholder exibido antes de selecionar uma receita *}
                    <p id="vars-placeholder" class="text-muted" style="font-size:0.85em;">
                        {lkn_hn_lang text="Select a recipe above to see the available variables."}
                    </p>

                    {* Macro reutilizável para o bloco de cliente — comum a todas as receitas *}
                    {capture name="client_vars"}
                        <strong style="font-size:0.8em; text-transform:uppercase; color:#888;">{lkn_hn_lang text="Client"}</strong>
                        <ul class="list-unstyled" style="margin: 4px 0 10px; font-size:0.88em;">
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}client_first_name{rdelim}{rdelim}"><code>{ldelim}{ldelim}client_first_name{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Client first name"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}client_full_name{rdelim}{rdelim}"><code>{ldelim}{ldelim}client_full_name{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Client full name"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}client_email{rdelim}{rdelim}"><code>{ldelim}{ldelim}client_email{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Client email"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}client_phone{rdelim}{rdelim}"><code>{ldelim}{ldelim}client_phone{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Client phone"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}client_company{rdelim}{rdelim}"><code>{ldelim}{ldelim}client_company{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Client company"}</li>
                        </ul>
                    {/capture}

                    {* Macro reutilizável para variáveis de sistema — comum a todas as receitas *}
                    {capture name="system_vars"}
                        <strong style="font-size:0.8em; text-transform:uppercase; color:#888;">{lkn_hn_lang text="System"}</strong>
                        <ul class="list-unstyled" style="margin: 4px 0 0; font-size:0.88em;">
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}company_name{rdelim}{rdelim}"><code>{ldelim}{ldelim}company_name{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Company name"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}company_email{rdelim}{rdelim}"><code>{ldelim}{ldelim}company_email{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Company email"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}date_today{rdelim}{rdelim}"><code>{ldelim}{ldelim}date_today{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Date today"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}whmcs_url{rdelim}{rdelim}"><code>{ldelim}{ldelim}whmcs_url{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="WHMCS URL"}</li>
                        </ul>
                    {/capture}

                    {* Receita: Fatura *}
                    <div id="vars-invoice" class="vars-group" style="display:none;">
                        <strong style="font-size:0.8em; text-transform:uppercase; color:#888;">{lkn_hn_lang text="Invoice"}</strong>
                        <ul class="list-unstyled" style="margin: 4px 0 10px; font-size:0.88em;">
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}invoice_id{rdelim}{rdelim}"><code>{ldelim}{ldelim}invoice_id{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Invoice ID"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}invoice_total{rdelim}{rdelim}"><code>{ldelim}{ldelim}invoice_total{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Total amount"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}invoice_balance{rdelim}{rdelim}"><code>{ldelim}{ldelim}invoice_balance{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Balance due"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}invoice_due_date{rdelim}{rdelim}"><code>{ldelim}{ldelim}invoice_due_date{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Due date"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}invoice_status{rdelim}{rdelim}"><code>{ldelim}{ldelim}invoice_status{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Invoice status"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}invoice_url{rdelim}{rdelim}"><code>{ldelim}{ldelim}invoice_url{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Invoice URL"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}invoice_pdf_url{rdelim}{rdelim}"><code>{ldelim}{ldelim}invoice_pdf_url{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="PDF link"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}invoice_items{rdelim}{rdelim}"><code>{ldelim}{ldelim}invoice_items{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Invoice items"}</li>
                        </ul>
                        {$smarty.capture.client_vars}
                        {$smarty.capture.system_vars}
                    </div>

                    {* Receita: Pedido *}
                    <div id="vars-order" class="vars-group" style="display:none;">
                        <strong style="font-size:0.8em; text-transform:uppercase; color:#888;">{lkn_hn_lang text="Order"}</strong>
                        <ul class="list-unstyled" style="margin: 4px 0 10px; font-size:0.88em;">
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}order_id{rdelim}{rdelim}"><code>{ldelim}{ldelim}order_id{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Order ID"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}order_items_descrip{rdelim}{rdelim}"><code>{ldelim}{ldelim}order_items_descrip{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Order items"}</li>
                        </ul>
                        {$smarty.capture.client_vars}
                        {$smarty.capture.system_vars}
                    </div>

                    {* Receita: Ticket *}
                    <div id="vars-ticket" class="vars-group" style="display:none;">
                        <strong style="font-size:0.8em; text-transform:uppercase; color:#888;">{lkn_hn_lang text="Ticket"}</strong>
                        <ul class="list-unstyled" style="margin: 4px 0 10px; font-size:0.88em;">
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}ticket_id{rdelim}{rdelim}"><code>{ldelim}{ldelim}ticket_id{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Ticket ID"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}ticket_subject{rdelim}{rdelim}"><code>{ldelim}{ldelim}ticket_subject{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Subject"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}ticket_status{rdelim}{rdelim}"><code>{ldelim}{ldelim}ticket_status{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Status"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}ticket_url{rdelim}{rdelim}"><code>{ldelim}{ldelim}ticket_url{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Ticket URL"}</li>
                        </ul>
                        {$smarty.capture.client_vars}
                        {$smarty.capture.system_vars}
                    </div>

                    {* Receita: Serviço *}
                    <div id="vars-module" class="vars-group" style="display:none;">
                        <strong style="font-size:0.8em; text-transform:uppercase; color:#888;">{lkn_hn_lang text="Service"}</strong>
                        <ul class="list-unstyled" style="margin: 4px 0 10px; font-size:0.88em;">
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}service_id{rdelim}{rdelim}"><code>{ldelim}{ldelim}service_id{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Service ID"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}service_name{rdelim}{rdelim}"><code>{ldelim}{ldelim}service_name{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Service name"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}service_domain{rdelim}{rdelim}"><code>{ldelim}{ldelim}service_domain{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Service domain"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}service_due_date{rdelim}{rdelim}"><code>{ldelim}{ldelim}service_due_date{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Service due date"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}service_status{rdelim}{rdelim}"><code>{ldelim}{ldelim}service_status{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Service status"}</li>
                        </ul>
                        {$smarty.capture.client_vars}
                        {$smarty.capture.system_vars}
                    </div>

                    {* Receita: Domínio *}
                    <div id="vars-domain" class="vars-group" style="display:none;">
                        <strong style="font-size:0.8em; text-transform:uppercase; color:#888;">{lkn_hn_lang text="Domain"}</strong>
                        <ul class="list-unstyled" style="margin: 4px 0 10px; font-size:0.88em;">
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}domain_name{rdelim}{rdelim}"><code>{ldelim}{ldelim}domain_name{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Domain name"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}domain_expiry_date{rdelim}{rdelim}"><code>{ldelim}{ldelim}domain_expiry_date{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Domain expiry date"}</li>
                            <li><a href="#" class="var-insert" data-var="{ldelim}{ldelim}domain_days_until_expiry{rdelim}{rdelim}"><code>{ldelim}{ldelim}domain_days_until_expiry{rdelim}{rdelim}</code></a> — {lkn_hn_lang text="Domain days until expiry"}</li>
                        </ul>
                        {$smarty.capture.client_vars}
                        {$smarty.capture.system_vars}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        {* Preenche automaticamente o campo code a partir do nome *}
        document.getElementById('notif-label').addEventListener('input', function () {
            var raw = this.value
                .replace(/[^a-zA-Z0-9\s]/g, '')
                .replace(/\s+(.)/g, function (m, c) { return c.toUpperCase(); })
                .replace(/\s/g, '');
            if (raw.length > 0) {
                raw = raw.charAt(0).toUpperCase() + raw.slice(1);
            }
            document.getElementById('notif-code').value = raw;
        });

        {* Mostra campo "Dias" apenas quando hook = DailyCronJob *}
        document.getElementById('notif-hook').addEventListener('change', function () {
            var fieldDays = document.getElementById('field-days');
            var inputDays = document.getElementById('notif-days');
            if (this.value === 'DailyCronJob') {
                fieldDays.style.display = 'block';
                inputDays.required = true;
            } else {
                fieldDays.style.display = 'none';
                inputDays.required = false;
                inputDays.value = '';
            }
        });

        {* Atualiza painel de variáveis conforme receita selecionada *}
        document.getElementById('notif-recipe').addEventListener('change', function () {
            document.querySelectorAll('.vars-group').forEach(function (el) { el.style.display = 'none'; });
            document.getElementById('vars-placeholder').style.display = 'none';
            var panel = document.getElementById('vars-' + this.value);
            if (panel) {
                panel.style.display = 'block';
            } else {
                document.getElementById('vars-placeholder').style.display = 'block';
            }
        });

        {* Inserção de variável no cursor do textarea ao clicar *}
        document.querySelectorAll('.var-insert').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var textarea = document.getElementById('notif-template');
                var variable = this.getAttribute('data-var');
                var start = textarea.selectionStart;
                var end = textarea.selectionEnd;
                textarea.value = textarea.value.substring(0, start) + variable + textarea.value.substring(end);
                textarea.focus();
                textarea.selectionStart = textarea.selectionEnd = start + variable.length;
            });
        });

        $(function () { $('[data-toggle="tooltip"]').tooltip(); });
    </script>
{/block}
