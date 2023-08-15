{*Copyright (c) 2023 Payfast (Pty) Ltd
You (being anyone who is not Payfast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active Payfast account. If your Payfast account is terminated for any reason, you may not use this plugin / code or part thereof.
Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.*}
<p>{l s='Your order on' mod='payfast'} <span
            class="bold">{Configuration::get('PS_SHOP_NAME')}</span> {l s='is complete.' mod='payfast'}
    <br/><br/>
    {l s='You chose the Payfast method.' mod='payfast'}
    <br/><br/><span class="bold">{l s='Your order will be sent shortly.' mod='payfast'}</span>
    <br/><br/>{l s='For any questions or for further information, please contact our' mod='payfast'} <a
            href="{$link->getPageLink('contact-form.php', true)}">{l s='customer support' mod='payfast'}</a>.
</p>
