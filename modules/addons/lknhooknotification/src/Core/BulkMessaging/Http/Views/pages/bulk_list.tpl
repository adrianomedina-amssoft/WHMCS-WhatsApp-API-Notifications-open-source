{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {lkn_hn_lang text="WhatsApp Campaigns"}
{/block}

{block "title_right_side"}
    <a class="btn btn-link" href="?module=lknhooknotification&amp;page=bulk/calendar">
        <i class="far fa-calendar-alt"></i>
        {lkn_hn_lang text="Full calendar"}
    </a>
    <a class="btn btn-primary" href="?module=lknhooknotification&amp;page=bulk/new">
        <i class="fas fa-plus"></i>
        {lkn_hn_lang text="New Campaign"}
    </a>
{/block}

{block "page_content"}

    {* ── MINI CALENDAR (next 7 days) ──────────────────────────────────────── *}
    {if !empty($page_params.calendar)}
        <div class="row" style="margin-bottom: 16px;">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title" style="font-size:14px; font-weight:600;">
                            <i class="far fa-calendar-check" style="margin-right:6px;"></i>
                            {lkn_hn_lang text="Upcoming dispatches (next 7 days)"}
                        </h4>
                    </div>
                    <div class="panel-body" style="padding: 10px 16px;">
                        <div style="display:flex; flex-wrap:wrap; gap:8px;">
                            {foreach from=$page_params.calendar item=$entries key=$date}
                                <div style="border:1px solid {if $entries[0].has_conflict}#f0ad4e{else}#ddd{/if};
                                    border-radius:4px; padding:8px 12px; min-width:140px; background:{if $entries[0].has_conflict}#fff8e8{else}#fafafa{/if};">
                                    <div style="font-weight:600; font-size:12px; color:#555; margin-bottom:4px;">
                                        {$date}
                                        {if $entries[0].has_conflict}
                                            <i class="fas fa-exclamation-triangle text-warning" style="margin-left:4px;"
                                               title="{lkn_hn_lang text='Conflict'}"></i>
                                        {/if}
                                    </div>
                                    {foreach from=$entries item=$entry}
                                        <div style="font-size:12px; margin-bottom:2px;">
                                            <span class="text-muted">{$entry.time}</span>
                                            <a href="?module=lknhooknotification&page=bulks/{$entry.id}"
                                               style="color:#333; text-decoration:none;">
                                                #{$entry.id} {$entry.title|truncate:18:'...':true}
                                            </a>
                                        </div>
                                    {/foreach}
                                </div>
                            {/foreach}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {/if}

    {* ── CAMPAIGN LIST ─────────────────────────────────────────────────────── *}
    <div class="row">
        <div class="col-md-12">

            {if count($page_params.bulks) === 0}
                <div class="alert alert-info" role="alert">
                    {lkn_hn_lang text="No campaigns yet."}
                </div>
                <a class="btn btn-link" href="?module=lknhooknotification&amp;page=bulk/new">
                    <i class="fas fa-plus"></i> {lkn_hn_lang text="New Campaign"}
                </a>
            {else}

                {* Group campaigns by status *}
                {assign var="groups" value=[
                    'active'      => [],
                    'in_progress' => [],
                    'paused'      => [],
                    'awaiting'    => [],
                    'completed'   => [],
                    'aborted'     => []
                ]}
                {foreach from=$page_params.bulks item=$bulk}
                    {if $bulk->status->value === 'active'}
                        {$groups.active[] = $bulk}
                    {elseif $bulk->status->value === 'in_progress'}
                        {$groups.in_progress[] = $bulk}
                    {elseif $bulk->status->value === 'paused'}
                        {$groups.paused[] = $bulk}
                    {elseif $bulk->status->value === 'completed'}
                        {$groups.completed[] = $bulk}
                    {elseif $bulk->status->value === 'aborted'}
                        {$groups.aborted[] = $bulk}
                    {else}
                        {$groups.awaiting[] = $bulk}
                    {/if}
                {/foreach}

                {* Render each status section *}
                {foreach from=[
                    ['key'=>'in_progress', 'label'=>'In progress',  'class'=>'info'],
                    ['key'=>'active',      'label'=>'Active',        'class'=>'success'],
                    ['key'=>'awaiting',    'label'=>'Awaiting',      'class'=>'default'],
                    ['key'=>'paused',      'label'=>'Paused',        'class'=>'default'],
                    ['key'=>'completed',   'label'=>'Completed',     'class'=>'success'],
                    ['key'=>'aborted',     'label'=>'Aborted',       'class'=>'warning']
                ] item=$section}
                    {if !empty($groups[$section.key])}
                        <h5 style="margin-top:20px; margin-bottom:8px; font-weight:600; color:#555; text-transform:uppercase; font-size:11px; letter-spacing:.5px;">
                            <span class="label label-{$section.class}" style="font-size:11px; margin-right:6px;">
                                {count($groups[$section.key])}
                            </span>
                            {lkn_hn_lang text=$section.label}
                        </h5>
                        <div class="panel panel-default" style="margin-bottom:8px;">
                            <div class="table-responsive">
                                <table class="table table-hover table-condensed" style="margin-bottom:0;">
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
                                        {foreach from=$groups[$section.key] item=$bulk}
                                            <tr>
                                                <th scope="row">{$bulk->id}</th>
                                                <td>
                                                    <a href="?module=lknhooknotification&page=bulks/{$bulk->id}">
                                                        {$bulk->title}
                                                    </a>
                                                    {if $bulk->description}
                                                        <br><small class="text-muted">{$bulk->description|truncate:50:'...':true}</small>
                                                    {/if}
                                                </td>
                                                <td>
                                                    <span class="label {$bulk->status->labelClass()}">{$bulk->status->label()}</span>
                                                </td>
                                                <td>
                                                    {if $bulk->recurrenceType && $bulk->recurrenceType !== 'once'}
                                                        <span class="label label-info" style="text-transform:none;">
                                                            {lkn_hn_lang text=$bulk->recurrenceType}
                                                        </span>
                                                    {else}
                                                        <span class="text-muted">{lkn_hn_lang text="One-time"}</span>
                                                    {/if}
                                                </td>
                                                <td>
                                                    {if $bulk->nextRunAt}
                                                        <span style="white-space:nowrap;">{$bulk->nextRunAt->format('d/m/Y H:i')}</span>
                                                    {else}
                                                        <span class="text-muted">-</span>
                                                    {/if}
                                                </td>
                                                <td style="min-width:90px;">
                                                    <div class="progress" style="margin-bottom:0;">
                                                        <div class="progress-bar" role="progressbar"
                                                             style="width:{$bulk->progress}%;">
                                                            {if $bulk->progress > 10}{$bulk->progress}%{/if}
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">{$bulk->progress}%</small>
                                                </td>
                                                <td>{$bulk->platform->label()}</td>
                                                <td style="white-space:nowrap;">{$bulk->startAt->format('d/m/Y H:i')}</td>
                                                <td style="white-space:nowrap;">
                                                    {* Edit *}
                                                    <a class="btn btn-default btn-xs"
                                                       href="?module=lknhooknotification&page=bulks/{$bulk->id}"
                                                       title="{lkn_hn_lang text='Edit'}">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    {* Pause (active only) *}
                                                    {if $bulk->status->value === 'active'}
                                                        <a class="btn btn-warning btn-xs"
                                                           href="?module=lknhooknotification&page=bulk/list&pause-campaign=1&bulk-id={$bulk->id}"
                                                           title="{lkn_hn_lang text='Pause'}">
                                                            <i class="fas fa-pause"></i>
                                                        </a>
                                                    {/if}

                                                    {* Resume (paused only) *}
                                                    {if $bulk->status->value === 'paused'}
                                                        <a class="btn btn-success btn-xs"
                                                           href="?module=lknhooknotification&page=bulk/list&resume-campaign=1&bulk-id={$bulk->id}"
                                                           title="{lkn_hn_lang text='Resume'}">
                                                            <i class="fas fa-play"></i>
                                                        </a>
                                                    {/if}

                                                    {* Send now (active campaigns only) *}
                                                    {if $bulk->status->value === 'active'}
                                                        <a class="btn btn-link btn-xs"
                                                           href="?module=lknhooknotification&page=bulk/list&send-now=1&bulk-id={$bulk->id}"
                                                           title="{lkn_hn_lang text='Send now'}"
                                                           onclick="return confirm('{lkn_hn_lang text='Send this campaign now?'}')">
                                                            <i class="fas fa-bolt"></i>
                                                        </a>
                                                    {/if}

                                                    {* Duplicate *}
                                                    <form style="display:inline;" method="POST"
                                                          action="?module=lknhooknotification&page=bulk/duplicate">
                                                        <input type="hidden" name="bulk-id" value="{$bulk->id}">
                                                        <button type="submit" class="btn btn-default btn-xs"
                                                                title="{lkn_hn_lang text='Duplicate'}"
                                                                onclick="return confirm('{lkn_hn_lang text='Duplicate this campaign?'}')">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </form>

                                                    {* History *}
                                                    <a class="btn btn-link btn-xs"
                                                       href="?module=lknhooknotification&page=bulks/{$bulk->id}/history"
                                                       title="{lkn_hn_lang text='History'}">
                                                        <i class="fas fa-history"></i>
                                                    </a>

                                                    {* Delete *}
                                                    {if $bulk->status->value !== 'in_progress' || $bulk->progress == 0}
                                                        <form style="display:inline;" method="POST"
                                                              action="?module=lknhooknotification&page=bulk/delete">
                                                            <input type="hidden" name="bulk-id" value="{$bulk->id}">
                                                            <button type="submit" class="btn btn-danger btn-xs"
                                                                    title="{lkn_hn_lang text='Delete'}"
                                                                    onclick="return confirm('{lkn_hn_lang text='Permanently delete this campaign? This action cannot be undone.'}')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    {/if}
                                                </td>
                                            </tr>
                                        {/foreach}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    {/if}
                {/foreach}

            {/if}
        </div>
    </div>
{/block}
