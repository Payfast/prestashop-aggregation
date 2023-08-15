{*
* Copyright (c) 2023 Payfast (Pty) Ltd
* You (being anyone who is not Payfast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active Payfast account. If your Payfast account is terminated for any reason, you may not use this plugin / code or part thereof.
* Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
*}

{if $status == 'ok'}
    <p>{l s='Your order on' mod='payfast'} <span class="bold">{$shop_name}</span> {l s='is complete.' mod='payfast'}
        <br/><br/><span class="bold">{l s='Your order will be shipped as soon as possible.' mod='payfast'}</span>
        <br/><br/>{l s='For any questions or for further information, please contact our' mod='payfast'} <a
                href="{$link->getPageLink('contact', true)}">{l s='customer support' mod='payfast'}</a>.
    </p>
{else}
    {if $status == 'pending'}
        <p>{l s='Your order on' mod='payfast'} <span class="bold">{$shop_name}</span> {l s='is pending.' mod='payfast'}
            <br/><br/><span
                    class="bold">{l s='Your order will be shipped as soon as we receive your bankwire.' mod='payfast'}</span>
            <br/><br/>{l s='For any questions or for further information, please contact our' mod='payfast'} <a
                    href="{$link->getPageLink('contact', true)}">{l s='customer support' mod='payfast'}</a>.
        </p>
    {else}
        <p class="warning">
            {l s='We noticed a problem with your order. If you think this is an error, you can contact our' mod='payfast'}
            <a href="{$link->getPageLink('contact', true)}">{l s='customer support' mod='payfast'}</a>.
        </p>
    {/if}
{/if}
