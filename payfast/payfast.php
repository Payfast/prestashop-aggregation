<?php

/*
 * payfast.php
 *
 *
 * @author     App Inlet
 * @version    1.2.3
 * @date       2024/08/21
 *
 * @link       https://payfast.io/integration/plugins/prestashop/
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Payfast extends PaymentModule
{
    private const LEFT_COLUMN  = 0;
    private const RIGHT_COLUMN = 1;
    private const FOOTER       = 2;
    private const DISABLE      = -1;
    private const CHECKED      = ' checked';
    private const PAYFASTURL   = 'https://payfast.io/';
    private const PFLINK       = 'pf__link';

    public function __construct()
    {
        if (!defined("PF_SOFTWARE_NAME")) {
            define('PF_SOFTWARE_NAME', 'PrestaShop');
            define('PF_SOFTWARE_VER', Configuration::get('PS_INSTALL_VERSION'));
            define('PF_MODULE_NAME', 'PF-Prestashop');
            define('PF_MODULE_VER', '1.2.3');
        }

        if (!defined("PF_DEBUG")) {
            define('PF_DEBUG', (bool)Configuration::get('PAYFAST_LOGS'));
        }

        $this->name                   = 'payfast';
        $this->tab                    = 'payments_gateways';
        $this->version                = constant('PF_MODULE_VER');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author                 = 'Payfast';
        $this->controllers            = array('validation');

        $this->currencies      = true;
        $this->currencies_mode = 'radio';

        parent::__construct();
        $this->page = basename(__FILE__, '.php');

        $this->displayName      = $this->l('Payfast');
        $this->description      = $this->l(
            'Accept payments via Payfast.'
        );
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');
    }

    public function install()
    {
        if (
            !parent::install()
            || !$this->registerHook('paymentOptions')
            || !$this->registerHook('displayPaymentReturn')
            || !Configuration::updateValue('PAYFAST_MERCHANT_ID', '')
            || !Configuration::updateValue('PAYFAST_MERCHANT_KEY', '')
            || !Configuration::updateValue('PAYFAST_LOGS', '1')
            || !Configuration::updateValue('PAYFAST_MODE', 'test')
            || !Configuration::updateValue('PAYFAST_PAYNOW_TEXT', 'Pay with Payfast')
            || !Configuration::updateValue('PAYFAST_PAYNOW_LOGO', 'on')
            || !Configuration::updateValue('PAYFAST_PAYNOW_ALIGN', 'right')
            || !Configuration::updateValue('PAYFAST_PASSPHRASE', '')
            || !Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_ENABLED', '0')
            || !Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_MERCHANT_ID', '')
            || !Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_AMOUNT', '')
            || !Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_PERCENTAGE', '')
            || !Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_MIN', '')
            || !Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_MAX', '')
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        return parent::uninstall()
               && Configuration::deleteByName('PAYFAST_MERCHANT_ID')
               && Configuration::deleteByName('PAYFAST_MERCHANT_KEY')
               && Configuration::deleteByName('PAYFAST_MODE')
               && Configuration::deleteByName('PAYFAST_LOGS')
               && Configuration::deleteByName('PAYFAST_PAYNOW_TEXT')
               && Configuration::deleteByName('PAYFAST_PAYNOW_LOGO')
               && Configuration::deleteByName('PAYFAST_PAYNOW_ALIGN')
               && Configuration::deleteByName('PAYFAST_PASSPHRASE')
               && Configuration::deleteByName('PAYFAST_SPLIT_PAYMENT_ENABLED')
               && Configuration::deleteByName('PAYFAST_SPLIT_PAYMENT_MERCHANT_ID')
               && Configuration::deleteByName('PAYFAST_SPLIT_PAYMENT_AMOUNT')
               && Configuration::deleteByName('PAYFAST_SPLIT_PAYMENT_PERCENTAGE')
               && Configuration::deleteByName('PAYFAST_SPLIT_PAYMENT_MIN')
               && Configuration::deleteByName('PAYFAST_SPLIT_PAYMENT_MAX');
    }

    public function getContent()
    {
        global $cookie;
        $errors = array();
        $html   = '
        <div id="pf__content">
        <div id="content" class="config__pf">
        <div class="pf__header">
            <p>
                <a href="https://payfast.io" target="_blank" rel="nofollow">
                    <img class="pf__logo" src="' . __PS_BASE_URI__ . 'modules/payfast/logo.svg"
                    alt="Payfast" border="0" style="width: auto; height: 60px;"/>
                </a>
            </p>
        </div>
        <div class="divider divider__longer"></div>';


        /* Update configuration variables */
        if (Tools::isSubmit('submitPayfast')) {
            if ($paynow_text = Tools::getValue('payfast_paynow_text')) {
                Configuration::updateValue('PAYFAST_PAYNOW_TEXT', $paynow_text);
            }

            if ($paynow_logo = Tools::getValue('logo_position')) {
                $isOn = $paynow_logo == -1 ? 'off' : 'on';
                Configuration::updateValue('PAYFAST_PAYNOW_LOGO', $isOn);
            }

            $position = match (Tools::getValue('logo_position')) {
                "0" => 'left',
                "1" => 'right',
                "2" => 'footer',
                default => 'none',
            };

            Configuration::updateValue('PAYFAST_PAYNOW_ALIGN', $position);

            $passPhrase = Tools::getValue('payfast_passphrase');
            Configuration::updateValue('PAYFAST_PASSPHRASE', $passPhrase);

            $mode = Tools::getValue('payfast_mode');
            Configuration::updateValue('PAYFAST_MODE', $mode);

            $merchant_id = Tools::getValue('payfast_merchant_id');
            Configuration::updateValue('PAYFAST_MERCHANT_ID', $merchant_id);

            $merchant_key = Tools::getValue('payfast_merchant_key');
            Configuration::updateValue('PAYFAST_MERCHANT_KEY', $merchant_key);

            $payfast_split_payments_enabled = Tools::getValue('payfast_split_payments_enabled');
            Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_ENABLED', $payfast_split_payments_enabled);

            $payfast_split_payment_merchant_id = Tools::getValue('payfast_split_payment_merchant_id');
            Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_MERCHANT_ID', $payfast_split_payment_merchant_id);

            $payfast_split_payment_amount = Tools::getValue('payfast_split_payment_amount');
            Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_AMOUNT', $payfast_split_payment_amount);

            $payfast_split_payment_percentage = Tools::getValue('payfast_split_payment_percentage');
            Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_PERCENTAGE', $payfast_split_payment_percentage);

            $payfast_split_payment_min = Tools::getValue('payfast_split_payment_min');
            Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_MIN', $payfast_split_payment_min);

            $payfast_split_payment_max = Tools::getValue('payfast_split_payment_max');
            Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_MAX', $payfast_split_payment_max);

            $payfast_logs = Tools::getValue('payfast_logs');
            Configuration::updateValue('PAYFAST_LOGS', $payfast_logs);

            foreach (array('displayLeftColumn', 'displayRightColumn', 'displayFooter') as $hookName) {
                if ($this->isRegisteredInHook($hookName)) {
                    $this->unregisterHook($hookName);
                }
            }
            if (Tools::getValue('logo_position') == self::LEFT_COLUMN) {
                $this->registerHook('displayLeftColumn');
            } elseif (Tools::getValue('logo_position') == self::RIGHT_COLUMN) {
                $this->registerHook('displayRightColumn');
            } elseif (Tools::getValue('logo_position') == self::FOOTER) {
                $this->registerHook('displayFooter');
            }
            if (method_exists('Tools', 'clearSmartyCache')) {
                Tools::clearSmartyCache();
            }
        }


        /* Display errors */
        if (sizeof($errors)) {
            $html .= '<ul style="color: red; font-weight: bold; width: 100%; background: #FFDFDF; ">';
            foreach ($errors as $error) {
                $html .= '<li> ' . $error . '</li>';
            }
            $html .= '</ul>';
        }


        $blockPositionList = array(
            self::DISABLE      => $this->l('Disable'),
            self::LEFT_COLUMN  => $this->l('Left Column'),
            self::RIGHT_COLUMN => $this->l('Right Column'),
            self::FOOTER       => $this->l('Footer')
        );

        if ($this->isRegisteredInHook('displayLeftColumn')) {
            $currentLogoBlockPosition = self::LEFT_COLUMN;
        } elseif ($this->isRegisteredInHook('displayRightColumn')) {
            $currentLogoBlockPosition = self::RIGHT_COLUMN;
        } elseif ($this->isRegisteredInHook('displayFooter')) {
            $currentLogoBlockPosition = self::FOOTER;
        } else {
            $currentLogoBlockPosition = -1;
        }


        /* Display settings form */
        $html .= '
        <head>
            <link href="' . __PS_BASE_URI__ . 'modules/payfast/views/css/payfast_styles.css" rel=\'stylesheet\'
             type=\'text/css\' />
            <script src="' . __PS_BASE_URI__ . 'modules/payfast/views/js/payfast_validate.js" ></script>
        </head>
        <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
          <div class="pf__main--section" id="main__section">
          <span class="main__section--header">Payfast Settings:</span>
            <div class="merchant__config">
              <div class="payfast__mode">
               <span class="pf__subheading">
               ' . $this->l('Mode:') . '
                 </span>
                   <div class="pf__selector">
                     <input type="radio" name="payfast_mode" value="live" ' . (
            Tools::getValue(
                'payfast_mode',
                Configuration::get('PAYFAST_MODE')
            ) == "live" ? self::CHECKED : '') . ' />' . $this->l('Live') . '&nbsp;&nbsp;</option>
                     <input type="radio" name="payfast_mode" value="test" ' . (Tools::getValue(
                'payfast_mode',
                Configuration::get(
                    'PAYFAST_MODE'
                )
            ) == "test" ? self::CHECKED : '') . ' />' . $this->l('Test') . '&nbsp;&nbsp;</option>
                   </div>
                </div>
              <p class="additional__info">' . $this->l(
                'Select "Test" mode to test sandbox payments, and "Live" mode when you are ready to go live.'
            ) . '</p>
            </div>

            <div class="divider"></div>

              <div class="merchant__details merchant__config">
                 <div class="account__details">
                    <span class="merchant__headers">
                        ' . $this->l('Merchant ID') . '
                    </span>
                    <input class="merchant__input"   type="number" step="0" min="0" name="payfast_merchant_id"
                     placeholder="e.g. 1000010.." value="' .
                 Tools::getValue(
                     'payfast_merchant_id',
                     Configuration::get('PAYFAST_MERCHANT_ID')
                 ) . '" />
                    <span class="merchant__headers">
                    ' . $this->l('Merchant Key') . '
                    </span>
                    <input class="merchant__input"   type="text" name="payfast_merchant_key"
                     placeholder="e.g. 46f0cd69458.." value="' .
                 trim(
                     Tools::getValue('payfast_merchant_key', Configuration::get('PAYFAST_MERCHANT_KEY'))
                 ) . '" />
                 </div>
             <p class="additional__info additional__info--smaller">' . $this->l(
                'You can find your Merchant ID and Merchant Key on your '
            ) . '<a id="' . self::PFLINK . '" href="' . self::PAYFASTURL . '">' .
                 $this->l('payfast.io') . '</a>' . $this->l(' account under DASHBOARD.') . '</p>
             </div>

            <div class="divider"></div>

            <div class="merchant__details merchant__config">
              <div class="account__details">
                <span class="merchant__headers">
                ' . $this->l('Secure Passphrase') . '
                </span>
                <input class="merchant__input"   type="text" name="payfast_passphrase"
                 placeholder="Same as your Payfast account" value="' .
                 trim(
                     Tools::getValue('payfast_passphrase', Configuration::get('PAYFAST_PASSPHRASE'))
                 ) . '" />
               </div>
                <p class="additional__info additional__info--taller">' . $this->l(
                'The passphrase is an optional/ extra security feature that must be set on your '
            ) . '<a id="' . self::PFLINK . '" href="' . self::PAYFASTURL . '">' .
                 $this->l('payfast.io') . '</a>' . $this->l(
                ' account in order to be used. You can find your passphrase under SETTINGS >
                 Integration SECURITY PASSPHRASE.'
            ) . '</p>' .
                 '
            </div>
            <div class="divider"></div>

            <div class="merchant__details merchant__config">
               <div class="account__details">
                   <span class="merchant__headers">
                    ' . $this->l('Enable Split Payments:') . '
                   </span>
               <div class="pf__selector split__selector">
                   <span class="merchant__headers">
                   ' . $this->l('Enable') . '
                   </span>
                   <input type="radio" name="payfast_split_payments_enabled"  value="1" ' .
                 (
                 empty(
                 Tools::getValue(
                     'payfast_split_payments_enabled',
                     Configuration::get('PAYFAST_SPLIT_PAYMENT_ENABLED')
                 )
                 ) ? '' : self::CHECKED
                 ) . ' />
                   <span class="merchant__headers">
                   ' . $this->l('Disable') . '
                   </span>
                   <input type="radio" name="payfast_split_payments_enabled"  value="0" ' .
                 (
                 empty(
                 Tools::getValue(
                     'payfast_split_payments_enabled',
                     Configuration::get('PAYFAST_SPLIT_PAYMENT_ENABLED')
                 )
                 ) ? self::CHECKED : '') . ' />
                </div>
           </div>
                <p class="additional__info additional__info--taller">' . $this->l(
                'Enable Split Payments to allow a portion of every payment to be split to a specified
                 receiving merchant. Split Payments must be enabled on your '
            ) . '<a id="' . self::PFLINK . '" href="' . self::PAYFASTURL . '">' .
                 $this->l('payfast.io') . '</a>' . $this->l(' account under SETTINGS > Integration.') . '</p>
           </div>

           <div class="merchant__details merchant__config">
              <div class="account__details">
                 <span class="merchant__headers">
                     ' . $this->l('Receiving Merchant ID') . '
                 </span>
                 <input class="merchant__input"   type="number" step="0" min="0"
                  name="payfast_split_payment_merchant_id" placeholder="e.g. 1000010.." value="' .
                 Tools::getValue(
                     'payfast_split_payment_merchant_id',
                     Configuration::get('PAYFAST_SPLIT_PAYMENT_MERCHANT_ID')
                 ) . '" />
                 </div>
          <p class="additional__info additional__info--smaller">' . $this->l(
                'This will be on the receiving merchants Payfast Dashboard.'
            ) . '</p>
          </div>

          <div class="merchant__details merchant__config">
             <div class="account__details">
                <span class="merchant__headers">
                    ' . $this->l('Amount in cents (ZAR)') . '
                </span>
                <input class="merchant__input"   type="number" step="0" min="0"
                  name="payfast_split_payment_amount" placeholder="e.g. 1000" value="' .
                 Tools::getValue(
                     'payfast_split_payment_amount',
                     Configuration::get('PAYFAST_SPLIT_PAYMENT_AMOUNT')
                 ) . '" />
                <span class="merchant__headers">
                ' . $this->l('Percentage') . '
                </span>
                <input class="merchant__input"   type="number" step="0" min="0" max="100"
                name="payfast_split_payment_percentage" placeholder="e.g. 10" value="' .
                 trim(
                     Tools::getValue(
                         'payfast_split_payment_percentage',
                         Configuration::get('PAYFAST_SPLIT_PAYMENT_PERCENTAGE')
                     )
                 ) . '" />
             </div>
         <p class="additional__info additional__info--smaller">' . $this->l(
                'Required amount in cents (ZAR) or/and percentage allocated to the receiving merchant of
                 a split payment.'
            ) . '</p>
         </div>

         <div class="merchant__details merchant__config">
            <div class="account__details">
               <span class="merchant__headers">
                   ' . $this->l('Min in cents (ZAR)') . '
               </span>
               <input class="merchant__input"   type="number" step="0" min="0"
                name="payfast_split_payment_min" placeholder="e.g. 500" value="' . Tools::getValue(
                'payfast_split_payment_min',
                Configuration::get('PAYFAST_SPLIT_PAYMENT_MIN')
            ) . '" />
               <span class="merchant__headers">
               ' . $this->l('Max in cents (ZAR)') . '
               </span>
               <input class="merchant__input"   type="number" step="0" min="0"
               name="payfast_split_payment_max" placeholder="e.g. 10000" value="' . trim(
                     Tools::getValue('payfast_split_payment_max', Configuration::get('PAYFAST_SPLIT_PAYMENT_MAX'))
                 ) . '" />
            </div>
        <p class="additional__info additional__info--smaller">' . $this->l(
                'Optional maximum or/and minimum amount that will be split, in cents (ZAR).'
            ) . '</p>
        </div>

            <div class="divider"></div>

             <div class="merchant__details merchant__config">
                <div class="account__details">
                    <span class="merchant__headers">
                     ' . $this->l('Debug to log server-to-server communication:') . '
                    </span>
                <div class="pf__selector debug__selector">
                    <span class="merchant__headers">
                    ' . $this->l('Enable') . '
                    </span>
                    <input type="radio" name="payfast_logs"  value="1" ' . (empty(
            Tools::getValue(
                'payfast_logs',
                Configuration::get(
                    'PAYFAST_LOGS'
                )
            )
            ) ? '' : self::CHECKED) . ' />
                    <span class="merchant__headers">
                    ' . $this->l('Disable') . '
                    </span>
                    <input type="radio" name="payfast_logs"  value="" ' . (empty(
            Tools::getValue(
                'payfast_logs',
                Configuration::get(
                    'PAYFAST_LOGS'
                )
            )
            ) ? self::CHECKED : '') . ' />
                 </div>
            </div>
                 <p class="additional__info additional__info--taller">' . $this->l(
                'Enable Debug to log the server-to-server communication. The log file for debugging can be found at '
            ) . ' ' . __PS_BASE_URI__ . 'modules/payfast/payfast.log. ' . $this->l(
                'If activated, be sure to protect it by putting a .htaccess file in the same directory.
                 If not, the file will be readable by everyone.'
            ) . '</p>
            </div>

            <div class="divider"></div>

            <div class="merchant__details merchant__config preview__section">
                <p class="additional__info additional__info--taller">' . $this->l(
                'The following payment option text is displayed during checkout.'
            ) . '</p>';

        //Pay now text field
        $html .= '<div class="account__details"><span class="merchant__headers">
                    ' . $this->l('Payment option text') . '
                  </span>

                  <input  class="merchant__input"   type="text" name="payfast_paynow_text" value="' .
                 Configuration::get(
                     'PAYFAST_PAYNOW_TEXT'
                 ) . '">
                  ';

        //Pay Now text preview.
        $html .= '<span class="merchant__headers preview__header">Preview</span>
                  <div>
                    ' . Configuration::get('PAYFAST_PAYNOW_TEXT') .
                 '&nbsp&nbsp<img alt="Pay with Payfast" title="Pay with Payfast" src="' . __PS_BASE_URI__ .
                 'modules/payfast/logo.svg" style="width: 150px; height: auto;">
                  </div>
               </div>
            </div>

            <div class="divider"></div>';

        //image position field
        $html .= '<div class="merchant__details merchant__config preview__section">
<p class="additional__info additional__info--taller">' . $this->l(
                'Select the position where the "Pay with Payfast" image will appear on your website.
                 This will be dependant on your theme.'
            ) . '</p>

            <div class="account__details">
            <span>
            ' . $this->l('Image position') . '
            </span>

            <select class="pf__dropdown" id="box" name="logo_position" >';
        foreach ($blockPositionList as $position => $translation) {
            $selected = ($currentLogoBlockPosition == $position) ? 'selected="selected"' : '';
            $html     .= '<option value="' . $position . '" ' . $selected . '>' . $translation . '</option>';
        }
        $html .= '
            </select>
          </div>
        </div>
      <div>
    <div class="divider"></div>
    <div>
        <button type="submit" name="submitPayfast" class="button" id="pf__button" value="Save">Save Changes</button>
        <div id="payfastDetailsError" style="display:none;color:red"></div>
    </div>
    <div class="clear">
    </div>
    </div>
    </form>
 </div>
 <div class="divider divider__longer"></div>
      <div class="pf__form--footer">
      <span class="footer__header">' . $this->l('Additional Information:') . '</span>
      <div class="footer__info">
      <span class="footer__info--para">- ' . $this->l(
                'In order to use your Payfast module, you must insert your Payfast Merchant ID and Merchant Key above.'
            ) . '</span>
      <span class="footer__info--para">- ' . $this->l(
                'Any orders in currencies other than ZAR will be converted by PrestaShop prior to be sent
                 to the Payfast payment gateway.'
            ) . '</span>
      <span class="footer__info--para">- ' . $this->l(
                'It is possible to setup an automatic currency rate update using crontab. You will simply
                 have to create a cron job with currency update link available at the bottom of "Currencies" section.'
            ) . '</span>
        </div>
    </div>

</div>
</div>
</div>';

        return $html;
    }

    public function hookDisplayRightColumn($params)
    {
        return $this->displayLogoBlock(self::RIGHT_COLUMN);
    }

    public function hookDisplayLeftColumn($params)
    {
        return $this->displayLogoBlock(self::LEFT_COLUMN);
    }

    public function hookDisplayFooter($params)
    {
        return '
        <section id="payfast_footer_link" class="footer-block col-xs-12 col-sm-2">
            <div style="text-align:center;">
                <a href="https://payfast.io" target="_blank rel="nofollow" title="Pay with Payfast">
                    <img src="' . __PS_BASE_URI__ . 'modules/payfast/logo.svg" style="width: 150px; height: auto;/>
                </a>
            </div>
        </section>';
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return [];
        }

        return [
            $this->getCardPaymentOption($params)
        ];
    }

    public function getCardPaymentOption($params)
    {
        global $cookie;
        $cart = $params['cart'];
        // Buyer details
        $customer = new Customer((int)($cart->id_customer));

        $toCurrency   = new Currency(Currency::getIdByIsoCode('ZAR'));
        $fromCurrency = new Currency((int)$cookie->id_currency);

        $total = $cart->getOrderTotal();

        $pfAmount = Tools::convertPriceFull($total, $fromCurrency, $toCurrency);

        $data = array();

        $currency = $this->getCurrency((int)$cart->id_currency);
        if ($cart->id_currency != $currency->id) {
            // If Payfast currency differs from local currency
            $cart->id_currency   = (int)$currency->id;
            $cookie->id_currency = (int)$cart->id_currency;
            $cart->update();
        }

        // Use appropriate merchant identifiers
        $pf_merchant_id  = Configuration::get('PAYFAST_MERCHANT_ID');
        $pf_merchant_key = Configuration::get('PAYFAST_MERCHANT_KEY');

        $data['info']['merchant_id']  = $pf_merchant_id;
        $data['info']['merchant_key'] = $pf_merchant_key;
        $passPhrase                   = Configuration::get('PAYFAST_PASSPHRASE');
        $data['payfast_url']          = Configuration::get(
            'PAYFAST_MODE'
        ) == 'live' ? 'https://www.payfast.co.za/eng/process' : 'https://sandbox.payfast.co.za/eng/process';

        $data['payfast_paynow_text']  = Configuration::get('PAYFAST_PAYNOW_TEXT');
        $data['payfast_paynow_logo']  = Configuration::get('PAYFAST_PAYNOW_LOGO');
        $data['payfast_paynow_align'] = Configuration::get('PAYFAST_PAYNOW_ALIGN');
        // Create URLs
        $data['info']['return_url']    = $this->context->link->getPageLink(
            'order-confirmation',
            null,
            null,
            'key=' . $cart->secure_key . '&id_cart=' . (int)($cart->id) . '&id_module=' . (int)($this->id)
        );
        $data['info']['cancel_url']    = Tools::getHttpHost(true) . __PS_BASE_URI__;
        $data['info']['notify_url']    = $this->context->link->getModuleLink($this->name, 'validation', array(), true);
        $data['info']['name_first']    = $customer->firstname;
        $data['info']['name_last']     = $customer->lastname;
        $data['info']['email_address'] = $customer->email;
        $data['info']['m_payment_id']  = $cart->id;
        $data['info']['amount']        = number_format(sprintf("%01.2f", $pfAmount), 2, '.', '');
        $data['info']['item_name']     = Configuration::get('PS_SHOP_NAME') . ' purchase, Cart Item ID #' . $cart->id;
        $data['info']['custom_int1']   = $cart->id;
        $data['info']['custom_str1']   = 'PF_PRESTASHOP_8_' . constant('PF_MODULE_VER');
        $data['info']['custom_str2']   = $cart->secure_key;

        $pfOutput = '';
        // Create output string
        foreach (($data['info']) as $key => $val) {
            $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
        }

        if (empty($passPhrase)) {
            $pfOutput = substr($pfOutput, 0, -1);
        } else {
            $pfOutput = $pfOutput . "passphrase=" . urlencode(trim($passPhrase));
        }

        $data['info']['signature'] = md5($pfOutput);

        //payfast values
        $payfastValues = array(
            'merchant_id'   => [
                'name'  => 'merchant_id',
                'type'  => 'hidden',
                'value' => $data['info']['merchant_id'],
            ],
            'merchant_key'  => [
                'name'  => 'merchant_key',
                'type'  => 'hidden',
                'value' => $data['info']['merchant_key'],
            ],
            'return_url'    => [
                'name'  => 'return_url',
                'type'  => 'hidden',
                'value' => $data['info']['return_url'],
            ],
            'cancel_url'    => [
                'name'  => 'cancel_url',
                'type'  => 'hidden',
                'value' => $data['info']['cancel_url'],
            ],
            'notify_url'    => [
                'name'  => 'notify_url',
                'type'  => 'hidden',
                'value' => $data['info']['notify_url'],
            ],
            'name_first'    => [
                'name'  => 'name_first',
                'type'  => 'hidden',
                'value' => $data['info']['name_first'],
            ],
            'name_last'     => [
                'name'  => 'name_last',
                'type'  => 'hidden',
                'value' => $data['info']['name_last'],
            ],
            'email_address' => [
                'name'  => 'email_address',
                'type'  => 'hidden',
                'value' => $data['info']['email_address'],
            ],
            'm_payment_id'  => [
                'name'  => 'm_payment_id',
                'type'  => 'hidden',
                'value' => $data['info']['m_payment_id'],
            ],
            'amount'        => [
                'name'  => 'amount',
                'type'  => 'hidden',
                'value' => $data['info']['amount'],
            ],
            'item_name'     => [
                'name'  => 'item_name',
                'type'  => 'hidden',
                'value' => $data['info']['item_name'],
            ],
            'custom_int1'   => [
                'name'  => 'custom_int1',
                'type'  => 'hidden',
                'value' => $data['info']['custom_int1'],
            ],
            'custom_str1'   => [
                'name'  => 'custom_str1',
                'type'  => 'hidden',
                'value' => $data['info']['custom_str1'],
            ],
            'custom_str2'   => [
                'name'  => 'custom_str2',
                'type'  => 'hidden',
                'value' => $data['info']['custom_str2'],
            ],
        );

        //add selected split payment values
        if (($split_payment = Configuration::get('PAYFAST_SPLIT_PAYMENT_ENABLED')) && !is_null($split_payment)) {
            $data['info']['setup']['split_payment']['merchant_id'] = Configuration::get(
                'PAYFAST_SPLIT_PAYMENT_MERCHANT_ID'
            );
            $data['info']['setup']['split_payment']['amount']      = Configuration::get('PAYFAST_SPLIT_PAYMENT_AMOUNT');
            $data['info']['setup']['split_payment']['percentage']  = Configuration::get(
                'PAYFAST_SPLIT_PAYMENT_PERCENTAGE'
            );
            $data['info']['setup']['split_payment']['min']         = Configuration::get('PAYFAST_SPLIT_PAYMENT_MIN');
            $data['info']['setup']['split_payment']['max']         = Configuration::get('PAYFAST_SPLIT_PAYMENT_MAX');

            $split_payment_array = array();
            foreach ($data['info']['setup']['split_payment'] as $key => $val) {
                if (!empty($val)) {
                    $split_payment_array[$key] = $val;
                }
            }
            $payfastValues['setup'] = [
                'name'  => 'setup',
                'type'  => 'hidden',
                'value' => json_encode(['split_payment' => $split_payment_array]),
            ];
        }

        $payfastValues['signature'] = [
            'name'  => 'signature',
            'type'  => 'hidden',
            'value' => $data['info']['signature'],
        ];

        $this->context->smarty->assign(['data' => $data]);

        $paymentForm = $this->fetch('module:payfast/views/templates/front/payfast.tpl');

        //create the payment option object
        $externalOption = new PaymentOption();
        $externalOption->setCallToActionText($this->l(Configuration::get('PAYFAST_PAYNOW_TEXT')))
                       ->setAction($data['payfast_url']) //link to payfast
                       ->setForm($paymentForm)
                       ->setInputs($payfastValues);

        return $externalOption;
    }

    public function hookDisplayPaymentReturn($params)
    {
        if (!$this->active) {
            return '';
        }

        return $this->fetch('module:payfast/payfast_success.tpl');
    }

    private function displayLogoBlock($position)
    {
        return '
            <div style="text-align:center;">
                <a href="https://payfast.io" target="_blank" rel="nofollow" title="Pay with Payfast">
                    <img src="' . __PS_BASE_URI__ . 'modules/payfast/logo.svg" style="width: 150px; height: auto;" />
                </a>
            </div>';
    }
}
