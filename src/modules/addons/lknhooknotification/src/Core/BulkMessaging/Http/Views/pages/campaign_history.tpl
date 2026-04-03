{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {lkn_hn_lang text="Dispatch History — [1]" params=[$page_params.bulk->title]}
{/block}

{block "title_right_side"}
    <a class="btn btn-link" href="?module=lknhooknotification&amp;page=bulks/{$page_params.bulk->id}">
        <i class="fas fa-arrow-left"></i>
        {lkn_hn_lang text="Back to campaign"}
    </a>
{/block}

{block "page_content"}
    <div class="row">
        <div class="col-md-12">
            {if count($page_params.runs) === 0}
                <div class="alert alert-info">
                    {lkn_hn_lang text="No dispatches recorded yet for this campaign."}
                </div>
            {else}
                <div class="panel panel-default">
                    <div class="table-responsive">
                        <table class="table table-hover table-condensed">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{lkn_hn_lang text="Started at"}</th>
                                    <th>{lkn_hn_lang text="Completed at"}</th>
                                    <th>{lkn_hn_lang text="Clients reached"}</th>
                                    <th>{lkn_hn_lang text="Status"}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$page_params.runs item=$run key=$key}
                                    <tr>
                                        <td>{$run->id}</td>
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
            {/if}
        </div>
    </div>
{/block}
