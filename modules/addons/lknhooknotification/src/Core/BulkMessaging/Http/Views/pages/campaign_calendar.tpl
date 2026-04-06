{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {lkn_hn_lang text="Campaign Calendar"}
{/block}

{block "title_right_side"}
    <a class="btn btn-link" href="?module=lknhooknotification&amp;page=bulk/list">
        <i class="fas fa-arrow-left"></i>
        {lkn_hn_lang text="Back to campaigns"}
    </a>
    <a class="btn btn-primary" href="?module=lknhooknotification&amp;page=bulk/new">
        <i class="fas fa-plus"></i>
        {lkn_hn_lang text="New Campaign"}
    </a>
{/block}

{block "page_content"}
    <div class="row">
        <div class="col-md-12">
            <p class="text-muted" style="margin-bottom:16px;">
                {lkn_hn_lang text="Next 30 days schedule. Dates with multiple campaigns at the same time are marked as conflicts."}
            </p>

            {if count($page_params.calendar) === 0}
                <div class="alert alert-info">
                    {lkn_hn_lang text="No scheduled campaigns in the next 30 days."}
                </div>
            {else}
                <div class="panel panel-default">
                    <div class="table-responsive">
                        <table class="table table-hover table-condensed">
                            <thead>
                                <tr>
                                    <th>{lkn_hn_lang text="Date"}</th>
                                    <th>{lkn_hn_lang text="Time"}</th>
                                    <th>{lkn_hn_lang text="Campaign"}</th>
                                    <th>{lkn_hn_lang text="Status"}</th>
                                    <th>{lkn_hn_lang text="Suggested time / Reschedule"}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$page_params.calendar item=$entries key=$date}
                                    {foreach from=$entries item=$entry}
                                        <tr {if $entry.has_conflict}style="background-color:#fff3cd;"{/if}>
                                            <td><strong>{$date}</strong></td>
                                            <td>{$entry.time}</td>
                                            <td>
                                                <a href="?module=lknhooknotification&page=bulks/{$entry.id}">
                                                    #{$entry.id} {$entry.title}
                                                </a>
                                            </td>
                                            <td>
                                                {if $entry.has_conflict}
                                                    <span class="label label-warning">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        {lkn_hn_lang text="Conflict"}
                                                    </span>
                                                {else}
                                                    <span class="label label-success">{lkn_hn_lang text="OK"}</span>
                                                {/if}
                                            </td>
                                            <td>
                                                {if $entry.suggested_next_run}
                                                    <div style="font-size:12px; color:#888; margin-bottom:4px;">
                                                        <i class="far fa-clock"></i>
                                                        {lkn_hn_lang text="Suggested"}: <strong>{$entry.suggested_display}</strong>
                                                    </div>
                                                    <form method="post"
                                                          action="?module=lknhooknotification&amp;page=bulk/reschedule"
                                                          style="display:flex; gap:4px; align-items:center;">
                                                        <input type="hidden" name="bulk-id" value="{$entry.id}">
                                                        <input type="datetime-local"
                                                               name="new-run-at"
                                                               value="{$entry.suggested_next_run}"
                                                               class="form-control input-sm"
                                                               style="width:160px;">
                                                        <button type="submit" class="btn btn-warning btn-xs">
                                                            <i class="fas fa-calendar-day"></i>
                                                            {lkn_hn_lang text="Reschedule"}
                                                        </button>
                                                    </form>
                                                {else}
                                                    —
                                                {/if}
                                            </td>
                                        </tr>
                                    {/foreach}
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>
            {/if}
        </div>
    </div>
{/block}
