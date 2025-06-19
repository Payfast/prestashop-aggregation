{*
* payfast.tpl
*
* Copyright (c) 2025 Payfast (Pty) Ltd
*
* @link       https://payfast.io/integration/plugins/prestashop/
*}
<div class='payfastPayNow'>
    <form id='payfastPayNow' action="{$data['payfast_url']}" method="post">
        <div class="payment_module"
             style="text-align: {if $data['payfast_paynow_align']=='left'}left{elseif $data['payfast_paynow_align']=='right'}right{elseif $data['payfast_paynow_align']=='footer'}center{/if};">
            {foreach $data['info'] as $k=>$v}
                <input type="hidden" name="{$k}" value="{$v}"/>
            {/foreach}
            <a href='#' onclick='document.getElementById("payfastPayNow").submit();return false;'>
                {if $data['payfast_paynow_logo']=='on'}
                    <img alt='Pay with Payfast' title='Pay with Payfast'
                         src="/modules/payfast/logo.svg"
                         style="width: 150px; height: auto;">
                {/if}
            </a>
        </div>
    </form>
</div>
<div class="clear"></div>
