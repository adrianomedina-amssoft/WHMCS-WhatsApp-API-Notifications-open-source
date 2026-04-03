{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {if $page_params.mode === 'edit'}
        {lkn_hn_lang text="Campaign #[1] — [2]" params=[$page_params.bulk->id, $page_params.bulk->title]}
    {else}
        {lkn_hn_lang text="New Campaign"}
    {/if}
{/block}

{block "page_content"}
    <style>
        #lkn-hn-new-bulk-form {
            max-width: 720px;
        }

        #lkn-hn-new-bulk-form label {
            text-align: left !important;
        }

        #lkn-hn-new-bulk-form .panel-body {
            padding: 50px;
        }

        {if $page_params.mode === 'edit'}
            #lkn-hn-msg-tpl-select-cont select {
                pointer-events: none;
                background-color: #cdcdcd38;
            }

        {/if}
    </style>

    <form
        id="lkn-hn-new-bulk-form"
        class="form-horizontal"
        target="_self"
        method="POST"
    >
        <div
            class="panel-group"
            id="accordion"
            role="tablist"
            aria-multiselectable="true"
        >
            {* STEP 1 *}

            <div class="panel panel-default">
                <div
                    class="panel-heading"
                    role="tab"
                    id="headingOne"
                >
                    <h4 class="panel-title">
                        <a
                            role="button"
                            data-toggle="collapse"
                            data-parent="#accordion"
                            href="#collapseOne"
                            aria-expanded="true"
                            aria-controls="collapseOne"
                        >
                            {lkn_hn_lang text="Details"}
                        </a>
                    </h4>
                </div>
                <div
                    id="collapseOne"
                    class="panel-collapse collapse in"
                    role="tabpanel"
                    aria-labelledby="headingOne"
                >
                    <div class="panel-body">
                        {if $page_params.mode === 'edit' && "now"|date_format:"%Y-%m-%d %H:%M:%S" >= $page_params.state->startAt->format('Y-m-d H:i:s')}
                            <div class="form-group">
                                <label
                                    for="title"
                                    class="col-sm-6 control-label"
                                >
                                    {lkn_hn_lang text="Progress"}
                                </label>
                                <div
                                    class="col-sm-6"
                                    style="display: flex; gap: 10px; align-items: start;"
                                >
                                    <div
                                        class="progress"
                                        style="flex-grow: 1;"
                                    >
                                        <div
                                            class="progress-bar"
                                            role="progressbar"
                                            aria-valuenow="{$page_params.bulk->progress}"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            style="width: {$page_params.bulk->progress}%;"
                                        >
                                            {$page_params.bulk->progress}%
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn btn-link"
                                        style="padding: 0px; outline: none;"
                                        onclick="document.querySelector('#lkn-hn-new-bulk-form').submit()"
                                        data-toggle="tooltip"
                                        data-placement="top"
                                        title="{lkn_hn_lang text="Refresh progress"}"
                                    >
                                        <i class="far fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label
                                    for="status"
                                    class="col-sm-6 control-label"
                                >
                                    {lkn_hn_lang text="Status"}
                                </label>
                                <div class="col-sm-6">
                                    <select
                                        class="form-control"
                                        id="bulk-status"
                                        name="bulk-status"
                                        required
                                        {if $page_params.bulk->status->value !== 'in_progress'}
                                            disabled
                                        {/if}
                                        onchange="confirmStatusChange()"
                                        disabled
                                    >
                                        {foreach from=$page_params.field_options['bulk_message_status'] item=$status}
                                            {if $status['value'] !== 'completed' || $page_params.bulk->status->value === 'completed'}
                                                <option
                                                    {if $status['value'] === $page_params.bulk->status->value}
                                                        selected
                                                    {/if}
                                                    value="{$status['value']}"
                                                >
                                                    {$status['label']}
                                                </option>
                                            {/if}
                                        {/foreach}
                                    </select>

                                    <div>
                                        {if $page_params.state->status->value === 'in_progress'}
                                            <button
                                                type="button"
                                                class="btn btn-link btn-sm"
                                                onclick="document.getElementById('bulk-status').disabled = false"
                                            >
                                                <i class="fas fa-exchange-alt"></i>
                                                {lkn_hn_lang text="Change bulk status"}
                                            </button>
                                        {/if}
                                    </div>

                                    <script type="text/javascript">
                                        function confirmStatusChange() {
                                            const result = confirm("{lkn_hn_lang text='Are you sure? The changes will affect in progress notifications too.'}");

                                            if (result) {
                                                document.getElementById('lkn-hn-new-bulk-form').submit()
                                            }
                                        }
                                    </script>
                                </div>
                            </div>
                        {/if}

                        {* DATE TO SEND *}

                        <div class="form-group">
                            <label
                                for="date-to-send"
                                class="col-sm-6 control-label"
                            >
                                {lkn_hn_lang text="Start date"}
                            </label>
                            <div class="col-sm-6">
                                <input
                                    type="datetime-local"
                                    class="form-control"
                                    id="date-to-send"
                                    name="date-to-send"
                                    required
                                    {if $page_params.mode === 'edit'}disabled{/if}
                                    {if $page_params.state->startAt}
                                        value="{$page_params.state->startAt->format('Y-m-d\TH:i')}"
                                    {/if}
                                >

                                <script>
                                    document.addEventListener('DOMContentLoaded', () => {
                                        const input = document.getElementById('date-to-send');
                                        const now = new Date();

                                        const pad = num => String(num).padStart(2, '0');
                                        const localDatetime = [
                                            now.getFullYear(),
                                            pad(now.getMonth() + 1),
                                            pad(now.getDate())
                                        ].join('-') + 'T' + [
                                            pad(now.getHours()),
                                            pad(now.getMinutes() + 5)
                                        ].join(':');

                                        input.min = localDatetime;
                                    });
                                </script>

                            </div>
                        </div>

                        <hr>

                        {* TITLE *}

                        <div class="form-group">
                            <label
                                for="title"
                                class="col-sm-6 control-label"
                            >
                                {lkn_hn_lang text="Title"}
                            </label>
                            <div class="col-sm-6">
                                <input
                                    type="text"
                                    class="form-control"
                                    id="title"
                                    name="title"
                                    required
                                    value="{$page_params.state->title}"
                                    {if $page_params.mode === 'edit'}disabled{/if}
                                >
                            </div>
                        </div>

                        {* Description *}

                        <div class="form-group">
                            <label
                                for="description"
                                class="col-sm-6 control-label"
                            >
                                {lkn_hn_lang text="Description"}
                            </label>
                            <div class="col-sm-6">
                                <input
                                    type="text"
                                    class="form-control"
                                    id="description"
                                    name="description"
                                    required
                                    value="{$page_params.state->descrip}"
                                    {if $page_params.mode === 'edit'}disabled{/if}
                                >
                            </div>
                        </div>

                        {* STATUS (create mode only) *}
                        {if $page_params.mode !== 'edit'}
                            <div class="form-group">
                                <label for="initial-status" class="col-sm-6 control-label">
                                    {lkn_hn_lang text="Status"}
                                </label>
                                <div class="col-sm-6">
                                    <select class="form-control" id="initial-status" name="initial-status">
                                        <option value="active" {if $page_params.state->status && $page_params.state->status->value === 'active'}selected{/if}>
                                            {lkn_hn_lang text="Active"}
                                        </option>
                                        <option value="paused" {if $page_params.state->status && $page_params.state->status->value === 'paused'}selected{/if}>
                                            {lkn_hn_lang text="Paused"}
                                        </option>
                                    </select>
                                    <span class="help-block">{lkn_hn_lang text="Active campaigns run on schedule. Paused campaigns can be resumed later."}</span>
                                </div>
                            </div>
                        {/if}

                        {* MAX SIMULTANEUS SENDING *}

                        <div class="form-group">
                            <div class="col-sm-6">
                                <label
                                    for="max-concurrency"
                                    class="control-label"
                                >
                                    {lkn_hn_lang text="Max concurrency"}
                                </label>
                                <span class="help-block">
                                    {lkn_hn_lang text="Number of shots per cron cycle, normally 5 minutes. Enter limit 2 to 50."}
                                </span>
                            </div>
                            <div class="col-sm-6">
                                <input
                                    type="number"
                                    max="50"
                                    min="2"
                                    class="form-control"
                                    id="max-concurrency"
                                    name="max-concurrency"
                                    required
                                    value="{if $page_params.state->maxConcurrency}{$page_params.state->maxConcurrency}{else}25{/if}"
                                    {if $page_params.mode === 'edit'}disabled{/if}
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {* FILTERS *}

            <div class="panel panel-default">
                <div
                    class="panel-heading"
                    role="tab"
                    id="headingTwo"
                >
                    <h4 class="panel-title">
                        <a
                            class="collapsed"
                            role="button"
                            data-toggle="collapse"
                            data-parent="#accordion"
                            href="#collapseTwo"
                            aria-expanded="false"
                            aria-controls="collapseTwo"
                        >
                            {lkn_hn_lang text="Filters"}
                        </a>
                    </h4>
                </div>
                <div
                    id="collapseTwo"
                    class="panel-collapse collapse {if $page_params.mode !== 'edit'}in{/if}"
                    role="tabpanel"
                    aria-labelledby="headingTwo"
                >
                    <div class="panel-body">
                        {* CLIENT STATUS *}

                        <div class="form-group">
                            <label
                                for="client-status"
                                class="col-sm-6 control-label"
                            >
                                {lkn_hn_lang text="Client status"}
                            </label>
                            <div class="col-sm-6">
                                <select
                                    class="form-control"
                                    id="client-status"
                                    name="client-status[]"
                                    multiple
                                    {if $page_params.mode === 'edit'}disabled{/if}
                                >
                                    {foreach from=$page_params.field_options.whmcs_client_statuses item=$status}
                                        <option
                                            {if $page_params.state->filters['client_status'] && in_array($status['value'], $page_params.state->filters['client_status']) || empty($page_params.state->filters['client_status'])}
                                                selected
                                            {/if}
                                            value="{$status['value']}"
                                        >
                                            {$status['label']}
                                        </option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>

                        {* CLIENT LOCALE *}

                        <div class="form-group">
                            <label
                                for="client-locale"
                                class="col-sm-6 control-label"
                            >
                                {lkn_hn_lang text="Client language"}
                            </label>
                            <div class="col-sm-6">
                                <select
                                    class="form-control"
                                    id="client-locale"
                                    name="client-locale[]"
                                    multiple
                                    {if $page_params.mode === 'edit'}disabled{/if}
                                >
                                    {* INÍCIO DA ALTERAÇÃO (Agora com o || empty) *}
                                    <option 
                                        value="default"
                                        {if (isset($page_params.state->filters['client_locale']) && in_array('default', $page_params.state->filters['client_locale'])) || empty($page_params.state->filters['client_locale'])}
                                            selected
                                        {/if}
                                    >
                                        {lkn_hn_lang text="Default"}
                                    </option>
                                    {* FIM DA ALTERAÇÃO *}

                                    {foreach from=$page_params.field_options.whmcs_client_lang item=$locale}
                                        <option
                                            {if (isset($page_params.state->filters['client_locale']) && in_array($locale['locale_expanded'], $page_params.state->filters['client_locale'])) || empty($page_params.state->filters['client_locale'])}
                                                selected
                                            {/if}
                                            value="{$locale['locale_expanded']}"
                                        >
                                            {$locale['label']}
                                        </option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>

                        {* CLIENT COUNTRY *}
                        <div class="form-group">
                            <label
                                for="client-country"
                                class="col-sm-6 control-label"
                            >
                                {lkn_hn_lang text="Client country"}
                            </label>
                            <div class="col-sm-6">
                                <select
                                    class="form-control"
                                    id="client-country"
                                    name="client-country[]"
                                    multiple
                                    {if $page_params.mode === 'edit'}disabled{/if}
                                >
                                    {foreach from=$page_params.field_options['whmcs_client_countries'] item=$country}
                                        <option
                                            {if $page_params.state->filters['client_country'] && in_array($country['value'], $page_params.state->filters['client_country']) || empty($page_params.state->filters['client_country'])}
                                                selected
                                            {/if}
                                            value="{$country['value']}"
                                        >
                                            {$country['label']}
                                        </option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>

                        <hr>
                        {* <div class="form-group">
                            <label class="col-sm-6 control-label">
                            </label>
                            <div class="col-sm-6">
                                <h2>
                                    <strong>
                                        {lkn_hn_lang text="[1] products matched"}
                                    </strong>
                                </h2>
                            </div>
                        </div> *}

                        {* SERVICES *}

                        <div class="form-group">
                            <label
                                for="services"
                                class="col-sm-6 control-label"
                            >
                                {lkn_hn_lang text="Services"}
                            </label>
                            <div class="col-sm-6">
                                <select
                                    class="form-control"
                                    id="services"
                                    name="services[]"
                                    multiple
                                    {if $page_params.mode === 'edit'}disabled{/if}
                                >
                                    {foreach from=$page_params.field_options['whmcs_products'] item=$product}
                                        <option
                                            {if $page_params.state->filters['services'] && in_array($product['value'], $page_params.state->filters['services'])}
                                                selected
                                            {/if}
                                            value="{$product['value']}"
                                        >
                                            {$product['label']}
                                        </option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>

                        {* SERVICE STATUS *}

                        <div class="form-group">
                            <label
                                for="service-status"
                                class="col-sm-6 control-label"
                            >
                                {lkn_hn_lang text="Service status"}
                            </label>
                            <div class="col-sm-6">
                                <select
                                    class="form-control"
                                    id="service-status"
                                    name="service-status[]"
                                    multiple
                                    {if $page_params.mode === 'edit'}disabled{/if}
                                >
                                    {foreach from=$page_params.field_options['whmcs_client_product_status'] item=$productStatus}
                                        <option
                                            {if $page_params.state->filters['service_status'] && in_array($productStatus['value'], $page_params.state->filters['service_status'])}
                                                selected
                                            {/if}
                                            value="{$productStatus['value']}"
                                        >
                                            {$productStatus['label']}
                                        </option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>

                        {* CLIENT GROUPS *}
                        {if !empty($page_params.field_options['client_groups'])}
                            <div class="form-group">
                                <label
                                    for="client-groups"
                                    class="col-sm-6 control-label"
                                >
                                    {lkn_hn_lang text="Client groups"}
                                </label>
                                <div class="col-sm-6">
                                    <select
                                        class="form-control"
                                        id="client-groups"
                                        name="client-groups[]"
                                        multiple
                                        {if $page_params.mode === 'edit'}disabled{/if}
                                    >
                                        {foreach from=$page_params.field_options['client_groups'] item=$group}
                                            <option
                                                {if $page_params.state->filters['client_groups'] && in_array($group['value'], $page_params.state->filters['client_groups'])}
                                                    selected
                                                {/if}
                                                value="{$group['value']}"
                                            >
                                                {$group['label']}
                                            </option>
                                        {/foreach}
                                    </select>
                                    <span class="help-block">{lkn_hn_lang text="Leave empty to include all groups."}</span>
                                </div>
                            </div>
                        {/if}

                        {* CLIENTS *}
                        {if $page_params.mode !== 'edit'}
                            <hr>
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <label
                                        for="client-ids"
                                        class="control-label"
                                    >
                                        {lkn_hn_lang text="Selected clients"}
                                        {if isset($page_params.field_options['client_options'])}
                                            <span id="selected-clients-count">
                                                {count($page_params.field_options['client_options'])}
                                            </span>
                                        {/if}
                                    </label>
                                    {if $page_params.mode !== 'edit'}
                                        <span class="help-block">
                                            {lkn_hn_lang text="If none is specified, the notification will be sent to all matched clients."}
                                        </span>
                                    {/if}
                                </div>
                                <div class="col-sm-12">
                                    {if isset($page_params.field_options['client_options'])}
                                        {if empty($page_params.field_options['client_options'])}
                                            <p class="text-danger">{lkn_hn_lang text="No client matched the filters."}</p>
                                        {else}
                                            <table
                                                id="table_id"
                                                class="display compact stripe"
                                            >
                                                <thead>
                                                    <tr>
                                                        <th>{lkn_hn_lang text="Client"}</th>
                                                        <th>{lkn_hn_lang text="Send?"}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {foreach from=$page_params.field_options['client_options'] item=$client}
                                                        <tr>
                                                            <td>#{$client['value']} {$client['label']}</td>
                                                            <td>
                                                                <input
                                                                    type="checkbox"
                                                                    {if !in_array($client['value'], $page_params.state->filters['not_sending_clients'])}
                                                                        checked
                                                                    {/if}
                                                                    data-will-send-client-id="{$client['value']}"
                                                                    onchange="handleNotSendToClient({$client['value']}, '{$client['label']}')"
                                                                >
                                                            </td>
                                                        </tr>
                                                    {/foreach}
                                                </tbody>
                                            </table>

                                            <div id="not-sending-cont">
                                                {foreach from=$page_params.state->filters['not_sending_clients'] item=$clientId}
                                                    <input
                                                        type="hidden"
                                                        id="not-sending-client-{$clientId}"
                                                        name="not-sending-clients[]"
                                                        value="{$clientId}"
                                                    />
                                                {/foreach}
                                                <div
                                                    id="not-sending-view"
                                                    style="display: flex; gap: 5px;"
                                                >
                                                    {foreach from=$page_params.state->filters['not_sending_clients'] item=$clientId}
                                                        <span
                                                            class="label label-default"
                                                            id="data-not-sending-client-id-{$clientId}"
                                                            data-container="data-not-sending-client-id-{$clientId}"
                                                            data-toggle="tooltip"
                                                            data-placement="top"
                                                            title="{lkn_hn_lang text="Send?"}"
                                                            style="text-transform: none; cursor: pointer;"
                                                            onclick="handleNotSendToClient({$clientId})"
                                                        >
                                                            # {$clientId}
                                                            <i
                                                                class="far fa-share"
                                                                style="margin-left: 4px;"
                                                            ></i>
                                                        </span>
                                                    {/foreach}
                                                </div>
                                            </div>


                                            <script src="https://cdn.datatables.net/2.3.0/js/dataTables.js"></script>
                                            <script type="text/javascript">
                                                const notSendingCont = document.getElementById('not-sending-cont')
                                                const notSendingView = document.getElementById('not-sending-view')
                                                const selectedClientCount = document.getElementById('selected-clients-count')

                                                let table = new DataTable('#table_id', {})

                                                function handleNotSendToClient(clientId, clientName = '') {
                                                    const isSelected = document.getElementById('not-sending-client-' + clientId)

                                                    if (!isSelected) {
                                                        selectedClientCount.innerText = parseInt(selectedClientCount.innerText) - 1
                                                        const clientNotSendingLabel = document.createElement('span')

                                                        clientNotSendingLabel.className = 'label label-default'

                                                        clientNotSendingLabel.id = 'data-not-sending-client-id-' + clientId
                                                        clientNotSendingLabel.setAttribute('data-toggle', 'tooltip')
                                                        clientNotSendingLabel.setAttribute('data-placement', 'top')
                                                        clientNotSendingLabel.setAttribute('title', '{lkn_hn_lang text="Send?"}')

                                                        clientNotSendingLabel.innerHTML = '#' + clientId + ' ' + clientName +
                                                            '<i class="far fa-share" style="margin-left: 4px;"></i>'
                                                        clientNotSendingLabel.style = 'text-transform: none; cursor: pointer;'

                                                        notSendingView.appendChild(clientNotSendingLabel)

                                                        clientNotSendingLabel.addEventListener('click', () => handleNotSendToClient(
                                                            clientId, clientName))

                                                        $(clientNotSendingLabel).tooltip({ container: '#' + clientNotSendingLabel.id })

                                                        const newClientNotSendingInput = document.createElement('input')

                                                        newClientNotSendingInput.id = 'not-sending-client-' + clientId
                                                        newClientNotSendingInput.name = 'not-sending-clients[]'
                                                        newClientNotSendingInput.value = clientId
                                                        newClientNotSendingInput.type = 'hidden'

                                                        notSendingCont.appendChild(newClientNotSendingInput)
                                                    } else {
                                                        selectedClientCount.innerText = parseInt(selectedClientCount.innerText) + 1
                                                        if (!isSelected) {
                                                            return
                                                        }

                                                        const clientNotSendingLabel = document.getElementById(
                                                            'data-not-sending-client-id-' + clientId)
                                                        const clientNotSendingInput = isSelected
                                                        const willSendCheckbox = document.querySelector(
                                                            'input[data-will-send-client-id="' + clientId + '"]')

                                                        willSendCheckbox.checked = true
                                                        clientNotSendingInput.remove()
                                                        clientNotSendingLabel.remove()
                                                    }
                                                }
                                            </script>

                                        {/if}
                                    {/if}

                                    {if $page_params.mode !== 'edit'}
                                        <input
                                            name="get-matched-clients"
                                            value="1"
                                            type="hidden"
                                        />
                                        <button
                                            id="btn-view-matched-clients"
                                            type="button"
                                            class="btn btn-success"
                                        >

                                            {lkn_hn_lang text='View matched clients'}
                                        </button>

                                        <script type="text/javascript">
                                            document.getElementById('btn-view-matched-clients').addEventListener('click', () => {
                                                document.getElementById('lkn-hn-new-bulk-form').submit()
                                            })
                                        </script>

                                    {/if}
                                </div>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>


            <div class="panel panel-default">
                <div
                    class="panel-heading"
                    role="tab"
                    id="headingTwo"
                >
                    <h4 class="panel-title">
                        <a
                            class="collapsed"
                            role="button"
                            data-toggle="collapse"
                            data-parent="#accordion"
                            href="#collapseDelta"
                            aria-expanded="false"
                            aria-controls="collapseDelta"
                        >
                            {lkn_hn_lang text="Message"}
                        </a>
                    </h4>
                </div>
                <div
                    id="collapseDelta"
                    class="panel-collapse collapse {if $page_params.mode !== 'edit'}in{/if}"
                    role="tabpanel"
                    aria-labelledby="headingTwo"
                >
                    <div class="panel-body">
                        {* PLATFORM *}

                        <div class="form-group">
                            <div class="col-sm-12">
                                <label
                                    for="platform"
                                    class="control-label"
                                >
                                    <h2>{lkn_hn_lang text='Platform'}</h2>
                                </label>
                            </div>
                            <div class="col-sm-12">
                                <select
                                    style="max-width: unset;"
                                    id="platform"
                                    name="platform"
                                    class="form-control"
                                    onchange="(document.getElementById('notification-form') ?? document.getElementById('lkn-hn-new-bulk-form')).submit()"
                                    {if $page_params.mode === 'edit'}disabled{/if}
                                >
                                    <option value="">{lkn_hn_lang text="Select a platform"}</option>

                                    {foreach from=$page_params.field_options['platform_options'] item=$platform}
                                        <option
                                            {if $platform === $page_params.state->platform}
                                                selected
                                            {/if}
                                            value="{$platform->value}"
                                        >
                                            {$platform->label()}
                                        </option>
                                    {/foreach}
                                </select>
                            </div>
                            {if $page_params.editing_template}
                                <div class="col-sm-12">
                                    <button
                                        id="btn-enable-platform-change"
                                        type="button"
                                        class="btn btn-link btn-sm"
                                    >
                                        <i class="fas fa-exchange-alt"></i>
                                        {lkn_hn_lang text="Change template platform"}
                                    </button>

                                    <script type="text/javascript">
                                        const btnEnablePlatformChange = document.getElementById('btn-enable-platform-change')

                                        btnEnablePlatformChange.addEventListener('click', () => {
                                            btnEnablePlatformChange.style.display = 'none'

                                            const platformSelect = document.getElementById('platform')

                                            platformSelect.readonly = false
                                            platformSelect.showPicker();
                                        })
                                    </script>
                                </div>
                            {/if}
                        </div>

                        {$page_params.template_editor_view}
                    </div>
                </div>
            </div>

            {if $page_params.mode === 'edit'}

                {include file="../components/bulk_messages.tpl"}

            {/if}


            {* ── RECURRENCE PANEL (create only) ───────────────────────────── *}
            {if $page_params.mode !== 'edit'}
                <div class="panel panel-default">
                    <div class="panel-heading" role="tab" id="headingRecurrence">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse"
                               data-parent="#accordion" href="#collapseRecurrence"
                               aria-expanded="false" aria-controls="collapseRecurrence">
                                {lkn_hn_lang text="Recurrence"}
                            </a>
                        </h4>
                    </div>
                    <div id="collapseRecurrence" class="panel-collapse collapse" role="tabpanel">
                        <div class="panel-body">

                            {* Recurrence type *}
                            <div class="form-group">
                                <label for="recurrence-type" class="col-sm-6 control-label">
                                    {lkn_hn_lang text="Recurrence type"}
                                </label>
                                <div class="col-sm-6">
                                    <select class="form-control" id="recurrence-type" name="recurrence-type"
                                        onchange="lknHnToggleRecurrenceFields(this.value)">
                                        {foreach from=$page_params.field_options.recurrence_types item=$rt}
                                            <option value="{$rt.value}"
                                                {if $page_params.state->recurrenceType === $rt.value}selected{/if}>
                                                {$rt.label}
                                            </option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>

                            {* DAILY / CUSTOM: interval in days *}
                            <div class="form-group lkn-rec-daily lkn-rec-custom" style="display:none;">
                                <label class="col-sm-6 control-label">{lkn_hn_lang text="Repeat every (days)"}</label>
                                <div class="col-sm-6">
                                    <input type="number" min="1" max="365" class="form-control"
                                        name="recurrence-interval" id="recurrence-interval" value="1">
                                </div>
                            </div>

                            {* WEEKLY: days of week + interval *}
                            <div class="form-group lkn-rec-weekly" style="display:none;">
                                <label class="col-sm-6 control-label">{lkn_hn_lang text="Days of week"}</label>
                                <div class="col-sm-6">
                                    {foreach from=[0,1,2,3,4,5,6] item=$dow}
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="recurrence-days-of-week[]" value="{$dow}">
                                            {if $dow === 0}{lkn_hn_lang text="Sun"}
                                            {elseif $dow === 1}{lkn_hn_lang text="Mon"}
                                            {elseif $dow === 2}{lkn_hn_lang text="Tue"}
                                            {elseif $dow === 3}{lkn_hn_lang text="Wed"}
                                            {elseif $dow === 4}{lkn_hn_lang text="Thu"}
                                            {elseif $dow === 5}{lkn_hn_lang text="Fri"}
                                            {else}{lkn_hn_lang text="Sat"}{/if}
                                        </label>
                                    {/foreach}
                                </div>
                            </div>

                            {* MONTHLY: day of month *}
                            <div class="form-group lkn-rec-monthly" style="display:none;">
                                <label class="col-sm-6 control-label">{lkn_hn_lang text="Day of month"}</label>
                                <div class="col-sm-6">
                                    <select class="form-control" name="recurrence-day-of-month">
                                        {foreach from=range(1,28) item=$d}
                                            <option value="{$d}">{$d}</option>
                                        {/foreach}
                                        <option value="first_business">{lkn_hn_lang text="First business day"}</option>
                                        <option value="last_business">{lkn_hn_lang text="Last business day"}</option>
                                    </select>
                                </div>
                            </div>

                            {* End date (all recurring types) *}
                            <div class="form-group lkn-rec-daily lkn-rec-weekly lkn-rec-monthly lkn-rec-custom" style="display:none;">
                                <label class="col-sm-6 control-label">{lkn_hn_lang text="End date (optional)"}</label>
                                <div class="col-sm-6">
                                    <input type="datetime-local" class="form-control" name="end-at" id="end-at"
                                        {if $page_params.state->endAt}value="{$page_params.state->endAt->format('Y-m-d\TH:i')}"{/if}>
                                    <span class="help-block">{lkn_hn_lang text="Leave empty for no end date."}</span>
                                </div>
                            </div>

                            {* Preview next dates *}
                            {if !empty($page_params.preview_dates)}
                                <div class="form-group">
                                    <div class="col-sm-12">
                                        <strong>{lkn_hn_lang text="Next 5 scheduled dates"}:</strong>
                                        <ul style="margin-top:8px;">
                                            {foreach from=$page_params.preview_dates item=$pd}
                                                <li>{$pd->format('d/m/Y H:i')}</li>
                                            {/foreach}
                                        </ul>
                                    </div>
                                </div>
                            {/if}

                        </div>
                    </div>
                </div>
            {/if}

            {* Campaign runs history (edit mode, recurring only) *}
            {if $page_params.mode === 'edit' && $page_params.bulk->isRecurring() && !empty($page_params.campaign_runs)}
                <div class="panel panel-default">
                    <div class="panel-heading" role="tab" id="headingRuns">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse"
                               data-parent="#accordion" href="#collapseRuns"
                               aria-expanded="false" aria-controls="collapseRuns">
                                {lkn_hn_lang text="Dispatch History"}
                                <span class="badge">{count($page_params.campaign_runs)}</span>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseRuns" class="panel-collapse collapse" role="tabpanel">
                        <div class="panel-body" style="padding:0;">
                            <table class="table table-condensed table-hover" style="margin-bottom:0;">
                                <thead>
                                    <tr>
                                        <th>{lkn_hn_lang text="Started at"}</th>
                                        <th>{lkn_hn_lang text="Completed at"}</th>
                                        <th>{lkn_hn_lang text="Clients reached"}</th>
                                        <th>{lkn_hn_lang text="Status"}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$page_params.campaign_runs item=$run}
                                        <tr>
                                            <td>{$run->started_at}</td>
                                            <td>{if $run->completed_at}{$run->completed_at}{else}-{/if}</td>
                                            <td>{$run->clients_reached}</td>
                                            <td>
                                                {if $run->status === 'completed'}
                                                    <span class="label label-success">{lkn_hn_lang text="Completed"}</span>
                                                {elseif $run->status === 'in_progress'}
                                                    <span class="label label-info">{lkn_hn_lang text="In progress"}</span>
                                                {else}
                                                    <span class="label label-default">{$run->status}</span>
                                                {/if}
                                            </td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            {/if}

            {if $page_params.mode !== 'edit'}
                <div class="form-group" style="margin-top: 60px;">
                    <div class="col-sm-12">
                        <button
                            type="submit"
                            class="btn btn-primary btn-block"
                            style="max-width: 160px; margin: 0 auto 0;"
                            onclick="return confirmSubmit('{lkn_hn_lang text='Do you really want to create the message? After confirmation, you will no longer be able to edit the message.'}')"
                            name="create-bulk"
                        >
                            {lkn_hn_lang text="Create Campaign"}
                        </button>
                    </div>
                </div>
            {/if}

            <script type="text/javascript">
                function lknHnToggleRecurrenceFields(type) {
                    document.querySelectorAll('[class*="lkn-rec-"]').forEach(function(el) {
                        el.style.display = 'none';
                    });
                    document.querySelectorAll('.lkn-rec-' + type).forEach(function(el) {
                        el.style.display = '';
                    });
                }
                // Init on page load
                document.addEventListener('DOMContentLoaded', function() {
                    var sel = document.getElementById('recurrence-type');
                    if (sel) lknHnToggleRecurrenceFields(sel.value);
                });
            </script>
        </div>
    </form>
{/block}
