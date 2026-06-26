{**
 * SearchProtect - Admin log table template
 *
 * @author    Tecnoacquisti.com
 * @copyright 2026 Tecnoacquisti.com - Arte e Informatica di Loris Modena e c. s.a.s.
 * @license   MIT
 *}

{if empty($sp_rows)}
    <div class="alert alert-info">
        {$sp_label_empty|escape:'html':'UTF-8'}
    </div>
{else}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-warning-sign"></i>
            {$sp_label_title|escape:'html':'UTF-8'}
        </div>
        <div class="table-responsive">
            <table class="table tableDnDcontainer">
                <thead>
                    <tr>
                        <th>{$sp_label_date|escape:'html':'UTF-8'}</th>
                        <th>{$sp_label_ip|escape:'html':'UTF-8'}</th>
                        <th>{$sp_label_reason|escape:'html':'UTF-8'}</th>
                        <th>{$sp_label_query|escape:'html':'UTF-8'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$sp_rows item=row}
                        <tr>
                            <td>{$row.blocked_at|escape:'html':'UTF-8'}</td>
                            <td><code>{$row.ip|escape:'html':'UTF-8'}</code></td>
                            <td>
                                <span class="badge badge-danger">
                                    {$row.reason|escape:'html':'UTF-8'}
                                </span>
                            </td>
                            <td>
                                <small>{$row.query|escape:'html':'UTF-8'|truncate:120:'…'}</small>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
{/if}
