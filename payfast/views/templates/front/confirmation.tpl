{*
 * Copyright (c) 2022 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 *}
{extends file='page.tpl'}
{block name='content'}
    <div class="card">
        <div class="card-block">
            <h1>
                {if empty($status) || $status == 2}
                    {l s='Transaction declined' mod='payfast'}
                {elseif $status == 3}
                    {l s='Transaction cancelled' mod='payfast'}
                {/if}
            </h1>
            <p>Please <a href="{$link->getPageLink('cart')}?action=show">{l s='click here' mod='payfast'}</a> to try
                again.</p>
        </div>
    </div>
    <br/>
{/block}
