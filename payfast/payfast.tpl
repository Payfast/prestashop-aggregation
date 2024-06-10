{
/*
* payfast.tpl
*
* Copyright (c) 2024 Payfast (Pty) Ltd
*
* @author     App Inlet
* @version    1.2.2
* @date       2024/06/10
*
* @link       https://payfast.io/integration/plugins/prestashop/
*/
}
<div class='payfastPayNow'>
    <form id='payfastPayNow' action="{$data.payfast_url}" method="post">
        <p class="payment_module">
            {foreach $data.info as $k=>$v}
                <input type="hidden" name="{$k}" value="{$v}"/>
            {/foreach}
            <a href='#'
               onclick='document.getElementById("payfastPayNow").submit();return false;'>{$data.payfast_paynow_text}
                {if $data.payfast_paynow_logo=='on'} <img align='{$data.payfast_paynow_align}'
                                                          alt='Pay with Payfast' title='Pay with Payfast'
                                                          src="{$base_dir}modules/payfast/logo.svg"
                                                          style="width: 150px; height: auto;">{/if}</a>
            <noscript><input type="image" src="{$base_dir}modules/payfast/logo.svg" style="width: 150px; height: auto;">
            </noscript>
        </p>
    </form>
</div>
<div class="clear"></div>
