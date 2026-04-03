{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {lkn_hn_lang text="WhatsApp Campaigns"}
{/block}

{block "title_right_side"}
    <a class="btn btn-link" href="?module=lknhooknotification&amp;page=bulk/calendar">
        <i class="far fa-calendar-alt"></i>
        {lkn_hn_lang text="Calendar"}
    </a>
    <a class="btn btn-primary" href="?module=lknhooknotification&amp;page=bulk/new">
        <i class="fas fa-plus"></i>
        {lkn_hn_lang text="New Campaign"}
    </a>
{/block}

{block "page_content"}
    <div class="row">
        <div class="col-md-12">
            {if count($page_params.bulks) === 0}
                <div class="alert alert-info" role="alert">
                    {lkn_hn_lang text="No campaigns yet."}
                </div>
                <a class="btn btn-link text-center" href="?module=lknhooknotification&amp;page=bulk/new">
                    <i class="fas fa-plus"></i>
                    {lkn_hn_lang text="New Campaign"}
                </a>
            {else}
                <div class="panel panel-default">
                    <div class="table-responsive">
                        <table class="table table-hover table-condensed">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{lkn_hn_lang text="Title"}</th>
                                    <th>{lkn_hn_lang text="Status"}</th>
                                    <th>{lkn_hn_lang text="Recurrence"}</th>
                                    <th>{lkn_hn_lang text="Next run"}</th>
                                    <th>{lkn_hn_lang text="Progress"}</th>
                                    <th>{lkn_hn_lang text="Platform"}</th>
                                    <th>{lkn_hn_lang text="Start date"}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$page_params.bulks item=$bulk key=$key}
                                    <tr>
                                        <th scope="row">{$bulk->id}</th>
                                        <td>{$bulk->title}</td>
                                        <td>
                                            {if "now"|date_format:"%Y-%m-%d %H:%M:%S" >= $bulk->startAt->format('Y-m-d H:i:s') || $bulk->status->value === 'active' || $bulk->status->value === 'paused'}
                                                <span class="label {$bulk->status->labelClass()}">{$bulk->status->label()}</span>
                                            {else}
                                                <span class="label label-default">{lkn_hn_lang text="Awaiting"}</span>
                                            {/if}
                                        </td>
                                        <td>
                                            {if $bulk->recurrenceType && $bulk->recurrenceType !== 'once'}
                                                <span class="label label-info">{$bulk->recurrenceType}</span>
                                            {else}
                                                <span class="text-muted">{lkn_hn_lang text="One-time"}</span>
                                            {/if}
                                        </td>
                                        <td>
                                            {if $bulk->nextRunAt}
                                                {$bulk->nextRunAt->format('d/m/Y H:i')}
                                            {else}
                                                -
                                            {/if}
                                        </td>
                                        <td>
                                            <div class="progress" style="margin-bottom:0;min-width:80px;">
                                                <div
                                                    class="progress-bar"
                                                    role="progressbar"
                                                    style="width:{$bulk->progress}%;"
                                                >{$bulk->progress}%</div>
                                            </div>
                                        </td>
                                        <td>{$bulk->platform->label()}</td>
                                        <td>{$bulk->startAt->format('d/m/Y H:i')}</td>
                                        <td style="white-space:nowrap;">
                                            <a class="btn btn-default btn-xs" href="?module=lknhooknotification&page=bulks/{$bulk->id}">
                                                <i class="fas fa-eye"></i> {lkn_hn_lang text="View"}
                                            </a>

                                            {* Pause / Resume *}
                                            {if $bulk->status->value === 'active'}
                                                <a class="btn btn-warning btn-xs" href="?module=lknhooknotification&page=bulk/list&pause-campaign=1&bulk-id={$bulk->id}">
                                                    <i class="fas fa-pause"></i> {lkn_hn_lang text="Pause"}
                                                </a>
                                            {/if}
                                            {if $bulk->status->value === 'paused'}
                                                <a class="btn btn-success btn-xs" href="?module=lknhooknotification&page=bulk/list&resume-campaign=1&bulk-id={$bulk->id}">
                                                    <i class="fas fa-play"></i> {lkn_hn_lang text="Resume"}
                                                </a>
                                            {/if}

                                            {* Send now (only for one-time awaiting) *}
                                            {if $bulk->recurrenceType === 'once' && $bulk->status->value === 'in_progress' && "now"|date_format:"%Y-%m-%d %H:%M:%S" < $bulk->startAt->format('Y-m-d H:i:s')}
                                                <a class="btn btn-link btn-xs" href="?module=lknhooknotification&page=bulk/list&send-now=1&bulk-id={$bulk->id}">
                                                    {lkn_hn_lang text="Send now"}
                                                </a>
                                            {/if}

                                            {* Duplicate *}
                                            <form style="display:inline;" method="POST" action="?module=lknhooknotification&page=bulk/duplicate">
                                                <input type="hidden" name="bulk-id" value="{$bulk->id}">
                                                <button type="submit" class="btn btn-default btn-xs"
                                                    onclick="return confirm('{lkn_hn_lang text='Duplicate this campaign?'}')">
                                                    <i class="fas fa-copy"></i> {lkn_hn_lang text="Duplicate"}
                                                </button>
                                            </form>

                                            {* History (recurring only) *}
                                            {if $bulk->isRecurring()}
                                                <a class="btn btn-link btn-xs" href="?module=lknhooknotification&page=bulks/{$bulk->id}/history">
                                                    <i class="fas fa-history"></i> {lkn_hn_lang text="History"}
                                                </a>
                                            {/if}
                                        </td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>
            {/if}
        </div>
    </div>
{/block}
