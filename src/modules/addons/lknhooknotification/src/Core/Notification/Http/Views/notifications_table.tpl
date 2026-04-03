{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {lkn_hn_lang text="Notifications"}
{/block}

{block "title_right_side"}
    <a
        class="btn btn-link"
        href="https://github.com/adrianomedina-amssoft/WHMCS-WhatsApp-API-Notifications-open-source/wiki/How-to-create-a-notification-by-yourself"
        target="_blank"
    >
        <i class="far fa-question-circle"></i>
        {lkn_hn_lang text="How to create your own notification?"}
    </a>
    <a
        class="btn btn-primary"
        href="{$lkn_hn_base_endpoint}&page=notifications/new"
    >
        <i class="fas fa-plus"></i>
        {lkn_hn_lang text="New Notification"}
    </a>
{/block}

{block "page_content"}
    {* Bloco de aviso de plano gratuito — sem link externo pago (projeto open source AMS SOFT) *}

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="table-responsive">
                    <table class="table table-hover table-condensed">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{lkn_hn_lang text="Status"}</th>
                                <th>{lkn_hn_lang text="Notification"}</th>
                                {* <th>{lkn_hn_lang text="Description"}</th> *}
                                <th>{lkn_hn_lang text="Templates"}</th>
                                {* <th>{lkn_hn_lang text="Actions"}</th> *}
                            </tr>
                        </thead>

                        <tbody>
                            {foreach from=$page_params.notifications item=$notification key=$key}
                                <tr style="position: relative;">
                                    <th scope="row">
                                        <p style="line-height: 30px;">
                                            {$key + 1}
                                        </p>
                                    </th>
                                    <td>
                                        <p style="line-height: 30px;">
                                            {if isset($notification->templates) && count($notification->templates) > 0}
                                                <span class="label label-success">{lkn_hn_lang text="Enabled"}</span>
                                            {else}
                                                <span class="label label-default">{lkn_hn_lang text="Disabled"}</span>
                                            {/if}
                                        </p>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; align-content: center; flex-wrap: wrap; max-width: fit-content;">
                                            <p style="margin-bottom: 2px;">
                                                {* Notificações dinâmicas exibem o label; as demais exibem o código traduzido *}
                                                {if isset($notification->label) && $notification->label}
                                                    {$notification->label}
                                                {else}
                                                    {lkn_hn_lang text=$notification->code}
                                                {/if}

                                                {* Ícone ? sempre visível — popover inicializado via JS com descrição, hook e destinatário *}
                                                <i
                                                    class="far fa-question-circle text-muted notif-help-icon"
                                                    style="cursor: pointer; margin-left: 4px;"
                                                    data-notif-name="{if isset($notification->label) && $notification->label}{$notification->label|escape:'html'}{else}{$notification->code|escape:'html'}{/if}"
                                                    data-notif-description="{if $notification->description}{$notification->description|escape:'html'}{/if}"
                                                    data-notif-hook="{$notification->hook->value|escape:'html'}"
                                                ></i>
                                            </p>

                                            {if $notification->hook->value}
                                                <a
                                                    href="https://developers.whmcs.com/hooks/hook-index/#:~:text={$notification->hook->value}"
                                                    target="_blank"
                                                    style="font-size: 0.85rem; color: gray;"
                                                >{$notification->hook->value}</a>
                                            {/if}
                                        </div>
                                    </td>
                                    {* <td></td> *}
                                    <td>
                                        <div style="display: flex; gap: 6px; margin-bottom: 6px; flex-wrap: wrap; align-items: center;">

                                            {* Botão Editar — para notificações dinâmicas abre o editor de metadados;
                                               para nativas abre o editor do primeiro template *}
                                            {if isset($notification->isDynamic) && $notification->isDynamic}
                                                <a
                                                    class="btn btn-default btn-sm"
                                                    href="{$lkn_hn_base_endpoint}&page=notifications/{$notification->code}/edit"
                                                >
                                                    <i class="fas fa-edit"></i>
                                                    {lkn_hn_lang text="Edit"}
                                                </a>
                                            {else}
                                                <a
                                                    class="btn btn-default btn-sm"
                                                    href="{$lkn_hn_base_endpoint}&page=notifications/{$notification->code}/templates/first"
                                                >
                                                    <i class="fas fa-edit"></i>
                                                    {lkn_hn_lang text="Edit"}
                                                </a>
                                            {/if}

                                            {if !$page_params.must_block_add_other_notifications}
                                                <a
                                                    type="button"
                                                    class="btn btn-link btn-sm"
                                                    href="{$lkn_hn_base_endpoint}&page=notifications/{$notification->code}/templates/new"
                                                >
                                                    <i class="fas fa-plus"></i>
                                                    {lkn_hn_lang text="Setup template"}
                                                </a>
                                            {/if}

                                            {* Botão clonar — disponível para qualquer notificação *}
                                            <form
                                                id="clone-notif-form-{$notification->code}"
                                                style="display: inline;"
                                                method="POST"
                                                action="{$lkn_hn_base_endpoint}&page=notifications/clone"
                                            >
                                                <input type="hidden" name="source-code" value="{$notification->code}">
                                                <button
                                                    type="submit"
                                                    class="btn btn-default btn-sm"
                                                    data-toggle="tooltip"
                                                    data-placement="top"
                                                    title="{lkn_hn_lang text='Clone this notification'}"
                                                    onclick="return window.confirm('{lkn_hn_lang text='Clone this notification?'}')"
                                                >
                                                    <i class="fas fa-copy"></i>
                                                    {lkn_hn_lang text="Clone"}
                                                </button>
                                            </form>

                                            {* Botão excluir — somente para notificações criadas pelo painel *}
                                            {if isset($notification->isDynamic) && $notification->isDynamic}
                                                <form
                                                    id="delete-custom-notif-form-{$notification->code}"
                                                    style="display: inline;"
                                                    method="POST"
                                                    action="{$lkn_hn_base_endpoint}&page=notifications/delete-custom"
                                                >
                                                    <input type="hidden" name="notification-code" value="{$notification->code}">
                                                    <button
                                                        type="submit"
                                                        class="btn btn-danger btn-sm"
                                                        onclick="return window.confirm('{lkn_hn_lang text='Are you sure you want to delete this notification and all its templates?'}')"
                                                    >
                                                        <i class="fas fa-trash"></i>
                                                        {lkn_hn_lang text="Delete"}
                                                    </button>
                                                </form>
                                            {/if}
                                        </div>
                                        {if isset($notification->templates) && (count($notification->templates) > 0)}
                                            <div
                                                class="panel panel-default"
                                                style="margin-bottom: 0px !important;"
                                            >
                                                <div class="table-responsive">
                                                    <table class="table table-hover table-condensed">
                                                        <tbody>
                                                            {foreach from=$notification->templates item=$template}
                                                                <tr>
                                                                    <td style="width: 80px;">
                                                                        <div style="display: flex; align-items: center; gap: 4px;">
                                                                            {if !in_array($template->platform, $page_params.platform_list)}
                                                                                <div
                                                                                    data-toggle="tooltip"
                                                                                    data-placement="left"
                                                                                    title="{lkn_hn_lang text="This template will not be sent because its is disabled."}"
                                                                                    style="background-color: red; width: 14px; height: 14px; border-radius: 100%;"
                                                                                >
                                                                                </div>
                                                                            {/if}

                                                                            <p
                                                                                class="text-muted"
                                                                                style="margin-bottom: 0px !important;"
                                                                            >
                                                                                {$template->lang}
                                                                            </p>
                                                                        </div>
                                                                    </td>
                                                                    <td style="width: 160px;">
                                                                        <p
                                                                            class="text-muted"
                                                                            style="margin-bottom: 0px !important;"
                                                                        >
                                                                            {$template->platform->label()}
                                                                        </p>
                                                                    </td>
                                                                    <td>
                                                                        <p
                                                                            {if $template->platform->value !== 'wp' && strlen($template->template) > 60}
                                                                                data-toggle="tooltip"
                                                                                data-placement="left"
                                                                                title="{$template->template}"
                                                                            {/if}
                                                                            class="text-muted"
                                                                            style="margin-bottom: 0px !important;"
                                                                        >
                                                                            {if strlen($template->template) > 60}
                                                                                {substr($template->template, 0, 60)}...
                                                                            {else}
                                                                                {$template->template}
                                                                            {/if}
                                                                        </p>
                                                                    </td>
                                                                    <td
                                                                        class="text-right"
                                                                        style="width: 140px;"
                                                                    >
                                                                        {* platforms/{platform}/notifications/{notif_code}/templates/{tpl_lang} *}

                                                                        {if !$page_params.must_block_edit_notification}
                                                                            <a
                                                                                class="btn btn-primary btn-xs"
                                                                                href="{$lkn_hn_base_endpoint}&page=notifications/{$notification->code}/templates/{$template->lang}"
                                                                            >
                                                                                {lkn_hn_lang text="Edit"}
                                                                            </a>
                                                                        {/if}

                                                                        <form
                                                                            id="delete-notif-form-{$notification->code}-{$template->lang}"
                                                                            style="display: none;"
                                                                            target="_self"
                                                                            method="POST"
                                                                        >
                                                                            <input
                                                                                type="hidden"
                                                                                name="delete-template"
                                                                            >

                                                                            <input
                                                                                type="hidden"
                                                                                name="notification-code"
                                                                                value="{$notification->code}"
                                                                            >

                                                                            <input
                                                                                type="hidden"
                                                                                name="template-locale"
                                                                                value="{$template->lang}"
                                                                            >
                                                                        </form>

                                                                        <button
                                                                            type="submit"
                                                                            form="delete-notif-form-{$notification->code}-{$template->lang}"
                                                                            class="btn btn-danger btn-xs"
                                                                            href="{$lkn_hn_base_endpoint}&page=notifications/{$notification->code}/templates/{$template->lang}"
                                                                            onclick="return window.confirm('{lkn_hn_lang text='Are you sure you want to delete this template?'}')"
                                                                        >
                                                                            {lkn_hn_lang text="Delete"}
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            {/foreach}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        {/if}


                                    </td>
                                    {* <td></td> *}
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        {* Inicializa o popover do ícone ? com descrição, hook disparador e destinatário *}
        $(function () {
            $('.notif-help-icon').each(function () {
                var name  = $(this).data('notif-name');
                var desc  = $(this).data('notif-description');
                var hook  = $(this).data('notif-hook');

                var content = '';

                if (desc) {
                    content += '<p style="margin-bottom:6px;">' + $('<div>').text(desc).html() + '</p>';
                    content += '<hr style="margin:4px 0 8px;">';
                }

                content += '<small>';
                content += '<b>{lkn_hn_lang text="Triggered by"}:</b> ' + $('<div>').text(hook).html() + '<br>';
                content += '<b>{lkn_hn_lang text="Recipient"}:</b> {lkn_hn_lang text="Client"}';
                content += '</small>';

                $(this).popover({
                    html:      true,
                    trigger:   'hover focus',
                    placement: 'right',
                    title:     $('<div>').text(name).html(),
                    content:   content,
                    container: 'body'
                });
            });
        });
    </script>
{/block}
