<input
    name="message-template-lang"
    type="hidden"
    value="{$page_params.editing_message_template['language']}"
>

<div class="form-group">
    <div class="col-sm-12">
        <label
            for="message-template"
            class="control-label"
        >
            <h2>{lkn_hn_lang text='Message Template'}</h2>
        </label>
    </div>

    <div class="col-sm-12">
        <select
            id="message-template"
            name="message-template"
            class="form-control"
            onchange="(document.getElementById('notification-form') ?? document.getElementById('lkn-hn-new-bulk-form')).submit()"
            {if $page_params.editing_message_template_name}
                readonly
            {/if}
        >
            <option value="">{lkn_hn_lang text="Select a platform"}</option>

            {foreach from=$page_params.message_templates_options item=$value key=$label}
                <option
                    value="{$value}"
                    {if $page_params.editing_message_template_name === $value}
                        selected
                    {/if}
                >
                    {$label}
                </option>
            {/foreach}
        </select>
    </div>

    {if $page_params.editing_message_template_name}
        <div class="col-sm-12">
            {if !$page_params.disable_template_editor_changes}

                <button
                    id="btn-enable-message-template-change"
                    type="button"
                    class="btn btn-link btn-sm"
                >
                    <i class="fas fa-exchange-alt"></i>
                    {lkn_hn_lang text="Change message template"}
                </button>
            {/if}

            <script type="text/javascript">
                const btnEnableMessageTemplateChange = document.getElementById('btn-enable-message-template-change')

                btnEnableMessageTemplateChange.addEventListener('click', () => {
                    btnEnableMessageTemplateChange.style.display = 'none'

                    const messageTemplateSelect = document.getElementById('message-template')

                    messageTemplateSelect.readonly = false
                    messageTemplateSelect.showPicker();
                })
            </script>
        </div>
    {/if}
</div>

{if $page_params.editing_message_template_name}
    <div class="form-group">
        <div class="col-sm-12">
            <div
                class="alert alert-info text-center"
                role="alert"
            >
                <i class="fas fa-caret-right"></i>
                {lkn_hn_lang text="Indicate for the notification what to put in the parameters of the message template created in Meta."}
            </div>
        </div>
    </div>
{else}
    <div
        class="alert alert-info"
        role="alert"
        style="margin-top: 40px;"
    >
        {lkn_hn_lang text="Please, choose a message template."}
    </div>

{/if}

<br>

<style>
    #lkn-hn-msg-tpl-select-cont select {
        border: 1px solid lightgray;
        border-radius: 6px;
    }
</style>

<div
    id="lkn-hn-msg-tpl-select-cont"
    class="form-group"
>
    <div class="col-sm-12">
        {foreach from=$page_params.editing_message_template['components'] item=$component}
            <div class="panel panel-default">
                <div class="panel-heading">
                    {lkn_hn_lang text=$component['type']}

                    {if !empty($component['format'])}
                        - {lkn_hn_lang text=$component['format']}
                    {/if}
                </div>

                <div
                    class="panel-body text-center"
                    style="display: flex; flex-direction: column; gap: 12px; align-items: center;"
                >
                    {if $component['type'] === 'HEADER'}

                        <input
                            type="hidden"
                            name="header-format"
                            value="{$component['format']}"
                        />

                        {if $page_params.editing_template_header_view === null}
                            {lkn_hn_lang text="This header type is not supported by the module."} ({$component['format']}).

                            <a
                                class="btn btn-link btn-sm"
                                href="https://github.com/LinkNacional/whmcs-whatsapp-api-notifications-open-source/issues/new?template=feature_request.md"
                                target="_blank"
                            >
                                {lkn_hn_lang text="Request this feature"} <i class="far fa-external-link-alt"></i>
                            </a>
                        {else}
                            {$page_params.editing_template_header_view}
                        {/if}

                    {elseif $component['type'] === 'BODY'}

                        <p
                            class="text-left"
                            style="margin-bottom: 0px !important; line-height: 36px;"
                        >
                            {$page_params.editing_template_body_view}
                        </p>

                    {elseif $component['type'] === 'FOOTER'}

                        {$component['text']}

                    {elseif $component['type'] === 'BUTTONS'}

                        {$page_params.editing_template_buttons_view}

                    {/if}
                </div>
            </div>
        {/foreach}
    </div>
</div>

{if $page_params.editing_message_template_name}
<div class="form-group">
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fab fa-whatsapp" style="color: #25D366;"></i>
                {lkn_hn_lang text='Message Preview'}
            </div>
            <div class="panel-body" style="background: #e5ddd5; padding: 20px; border-radius: 0 0 4px 4px;">
                <div style="max-width: 320px; margin: 0 auto;">
                    <div id="lkn-hn-wp-bubble" style="background: white; border-radius: 8px 8px 8px 2px; padding: 10px 12px 6px; box-shadow: 0 1px 2px rgba(0,0,0,.2);">
                        <div id="lkn-hn-preview-header" style="font-weight: 700; margin-bottom: 6px; display: none;"></div>
                        <div id="lkn-hn-preview-body" style="font-size: 14px; line-height: 1.6; word-wrap: break-word;"></div>
                        <div id="lkn-hn-preview-footer" style="color: #999; font-size: 12px; margin-top: 4px; display: none;"></div>
                        <div style="text-align: right; font-size: 11px; color: #aaa; margin-top: 6px;">12:00 &#10003;&#10003;</div>
                    </div>
                    <div id="lkn-hn-preview-buttons"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var lknMetaTplComponents = {$page_params.template_components_json};
    var lknPreviewPlaceholder = '{lkn_hn_lang text="Select parameters above to preview the message."}';
{literal}
    function highlight(text) {
        return '<span style="background:#d4f1d4;padding:1px 4px;border-radius:3px;font-weight:600;">' + text + '</span>';
    }

    function pending(placeholder) {
        return '<span style="background:#f0f0f0;color:#aaa;padding:1px 4px;border-radius:3px;">' + placeholder + '</span>';
    }

    function updateLknWpPreview() {
        var previewHeader  = document.getElementById('lkn-hn-preview-header');
        var previewBody    = document.getElementById('lkn-hn-preview-body');
        var previewFooter  = document.getElementById('lkn-hn-preview-footer');
        var previewButtons = document.getElementById('lkn-hn-preview-buttons');

        if (!previewBody) { return; }

        lknMetaTplComponents.forEach(function (comp) {
            if (comp.type === 'HEADER') {
                var sel = document.querySelector('[name="header-parameter"]');
                var headerHtml = '';
                if (comp.text && comp.text.indexOf('{{') === -1) {
                    headerHtml = comp.text;
                } else if (sel) {
                    headerHtml = sel.selectedIndex > 0
                        ? highlight(sel.options[sel.selectedIndex].text)
                        : pending('{{1}}');
                }
                previewHeader.innerHTML = headerHtml;
                previewHeader.style.display = headerHtml ? 'block' : 'none';

            } else if (comp.type === 'BODY') {
                var selects = document.querySelectorAll('[name="body-parameters[]"]');
                var bodyHtml = (comp.text || '').replace(/\{\{(\d+)\}\}/g, function (_, n) {
                    var sel = selects[parseInt(n, 10) - 1];
                    return (sel && sel.selectedIndex > 0)
                        ? highlight(sel.options[sel.selectedIndex].text)
                        : pending('{{' + n + '}}');
                }).replace(/\n/g, '<br>');
                previewBody.innerHTML = bodyHtml || '<span style="color:#ccc;">' + lknPreviewPlaceholder + '</span>';

            } else if (comp.type === 'FOOTER') {
                previewFooter.textContent = comp.text || '';
                previewFooter.style.display = comp.text ? 'block' : 'none';

            } else if (comp.type === 'BUTTONS') {
                var btns = (comp.buttons || []).map(function (b) {
                    return '<button type="button" class="btn btn-default btn-sm btn-block" style="margin-top:4px;border-radius:6px;background:white;border:1px solid #ddd;color:#128C7E;">' + b.text + '</button>';
                });
                previewButtons.innerHTML = btns.join('');
            }
        });
    }

    var cont = document.getElementById('lkn-hn-msg-tpl-select-cont');
    if (cont) {
        cont.addEventListener('change', updateLknWpPreview);
    }

    updateLknWpPreview();
}());
{/literal}
</script>
{/if}
