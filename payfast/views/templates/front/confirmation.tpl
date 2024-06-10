{*
* confirmation.tpl
*
* Copyright (c) 2024 Payfast (Pty) Ltd
*
* @author     App Inlet
* @version    1.2.2
* @date       2024/06/10
*
* @link       https://payfast.io/integration/plugins/prestashop/
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
