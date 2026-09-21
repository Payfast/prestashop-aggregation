<?php

/*
 * Copyright (c) 2026 Payfast (Pty) Ltd
 *
 * @link       https://payfast.io/integration/plugins/prestashop/
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Payfast extends PaymentModule
{
    private const    LEFT_COLUMN  = 0;
    private const    RIGHT_COLUMN = 1;
    private const    FOOTER       = 2;
    private const    DISABLE      = -1;
    private const    PAYFASTURL   = 'https://payfast.io/';
    private const    PFLINK       = 'pf__link';

    public function __construct()
    {
        if (!defined("PF_SOFTWARE_NAME")) {
            define('PF_SOFTWARE_NAME', 'PrestaShop');
            define('PF_SOFTWARE_VER', Configuration::get('PS_INSTALL_VERSION'));
            define('PF_MODULE_NAME', 'PF-Prestashop');
            define('PF_MODULE_VER', '1.4.0');
        }

        if (!defined("PF_DEBUG")) {
            define('PF_DEBUG', (bool)Configuration::get('PAYFAST_LOGS'));
        }

        $this->name                   = 'payfast';
        $this->tab                    = 'payments_gateways';
        $this->version                = constant('PF_MODULE_VER');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->author                 = 'Payfast';
        $this->controllers            = ['validation'];

        $this->currencies      = true;
        $this->currencies_mode = 'radio';

        parent::__construct();
        $this->page = basename(__FILE__, '.php');

        $this->displayName      = $this->l('Payfast Aggregation');
        $this->description      = $this->l(
            'Accept payments via Payfast Aggregation.'
        );
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');
    }

    /**
     * @throws PrestaShopException
     */
    public function install(): bool
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

    public function uninstall(): bool
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

    /**
     * @throws PrestaShopException
     */
    public function getContent(): string
    {
        // Handle form submission
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
            Configuration::updateValue('PAYFAST_PASSPHRASE', Tools::getValue('payfast_passphrase'));
            Configuration::updateValue('PAYFAST_MODE', Tools::getValue('payfast_mode'));
            Configuration::updateValue('PAYFAST_MERCHANT_ID', Tools::getValue('payfast_merchant_id'));
            Configuration::updateValue('PAYFAST_MERCHANT_KEY', Tools::getValue('payfast_merchant_key'));
            Configuration::updateValue(
                'PAYFAST_SPLIT_PAYMENT_ENABLED',
                Tools::getValue('payfast_split_payments_enabled')
            );
            Configuration::updateValue(
                'PAYFAST_SPLIT_PAYMENT_MERCHANT_ID',
                Tools::getValue('payfast_split_payment_merchant_id')
            );
            Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_AMOUNT', Tools::getValue('payfast_split_payment_amount'));
            Configuration::updateValue(
                'PAYFAST_SPLIT_PAYMENT_PERCENTAGE',
                Tools::getValue('payfast_split_payment_percentage')
            );
            Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_MIN', Tools::getValue('payfast_split_payment_min'));
            Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_MAX', Tools::getValue('payfast_split_payment_max'));
            Configuration::updateValue('PAYFAST_LOGS', Tools::getValue('payfast_logs'));

            foreach (['displayLeftColumn', 'displayRightColumn', 'displayFooter'] as $hookName) {
                if ($this->isRegisteredInHook($hookName)) {
                    $this->unregisterHook($hookName);
                }
            }

            match (Tools::getValue('logo_position')) {
                self::LEFT_COLUMN => $this->registerHook('displayLeftColumn'),
                self::RIGHT_COLUMN => $this->registerHook('displayRightColumn'),
                self::FOOTER => $this->registerHook('displayFooter'),
                default => null,
            };

            if (method_exists('Tools', 'clearSmartyCache')) {
                Tools::clearSmartyCache();
            }
        }

        $blockPositionList = [
            self::DISABLE      => $this->l('Disable'),
            self::LEFT_COLUMN  => $this->l('Left Column'),
            self::RIGHT_COLUMN => $this->l('Right Column'),
            self::FOOTER       => $this->l('Footer')
        ];

        $currentLogoBlockPosition = match (true) {
            $this->isRegisteredInHook('displayLeftColumn') => self::LEFT_COLUMN,
            $this->isRegisteredInHook('displayRightColumn') => self::RIGHT_COLUMN,
            $this->isRegisteredInHook('displayFooter') => self::FOOTER,
            default => -1,
        };

        // Gather variables for the template
        $templateVars = [
            'base_uri'                          => __PS_BASE_URI__,
            'request_uri'                       => htmlspecialchars(
                filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL) ?? '',
                ENT_QUOTES,
                'UTF-8'
            ),
            'payfast_mode'                      => Tools::getValue('payfast_mode', Configuration::get('PAYFAST_MODE')),
            'payfast_merchant_id'               => Tools::getValue(
                'payfast_merchant_id',
                Configuration::get('PAYFAST_MERCHANT_ID')
            ),
            'payfast_merchant_key'              => trim(
                Tools::getValue('payfast_merchant_key', Configuration::get('PAYFAST_MERCHANT_KEY'))
            ),
            'payfast_passphrase'                => trim(
                Tools::getValue('payfast_passphrase', Configuration::get('PAYFAST_PASSPHRASE'))
            ),
            'payfast_split_payments_enabled'    => Tools::getValue(
                'payfast_split_payments_enabled',
                Configuration::get('PAYFAST_SPLIT_PAYMENT_ENABLED')
            ),
            'payfast_split_payment_merchant_id' => Tools::getValue(
                'payfast_split_payment_merchant_id',
                Configuration::get(
                    'PAYFAST_SPLIT_PAYMENT_MERCHANT_ID'
                )
            ),
            'payfast_split_payment_amount'      => Tools::getValue(
                'payfast_split_payment_amount',
                Configuration::get('PAYFAST_SPLIT_PAYMENT_AMOUNT')
            ),
            'payfast_split_payment_percentage'  => trim(
                Tools::getValue(
                    'payfast_split_payment_percentage',
                    Configuration::get('PAYFAST_SPLIT_PAYMENT_PERCENTAGE')
                )
            ),
            'payfast_split_payment_min'         => Tools::getValue(
                'payfast_split_payment_min',
                Configuration::get('PAYFAST_SPLIT_PAYMENT_MIN')
            ),
            'payfast_split_payment_max'         => trim(
                Tools::getValue('payfast_split_payment_max', Configuration::get('PAYFAST_SPLIT_PAYMENT_MAX'))
            ),
            'payfast_logs'                      => Tools::getValue('payfast_logs', Configuration::get('PAYFAST_LOGS')),
            'payfast_paynow_text'               => Configuration::get('PAYFAST_PAYNOW_TEXT'),
            'blockPositionList'                 => $blockPositionList,
            'currentLogoBlockPosition'          => $currentLogoBlockPosition,
            'pf_link'                           => self::PFLINK,
            'payfast_url'                       => self::PAYFASTURL,
        ];

        // Render the settings page using Twig

        /** @var PrestaShop\PrestaShop\Core\Module\WidgetInterface $this */
        return $this->get('twig')->render(
            '@Modules/payfast/views/templates/admin/payfast_configure.twig',
            $templateVars
        );
    }

    public function hookDisplayRightColumn($params): string
    {
        return $this->displayLogoBlock(self::RIGHT_COLUMN);
    }

    public function hookDisplayLeftColumn($params): string
    {
        return $this->displayLogoBlock(self::LEFT_COLUMN);
    }

    public function hookDisplayFooter($params): string
    {
        return '
        <section id="payfast_footer_link" class="footer-block col-xs-12 col-sm-2">
            <div style="text-align:center;">
                <a href="https://payfast.io" target="_blank rel="nofollow" title="Pay with Payfast">
                    <img src="' . __PS_BASE_URI__ . 'modules/payfast/payfast-logo.svg" style="width: 150px; height: auto;/>
                </a>
            </div>
        </section>';
    }

    public function hookPaymentOptions($params): array
    {
        if (!$this->active) {
            return [];
        }

        return [
            $this->getCardPaymentOption($params)
        ];
    }

    public function getCardPaymentOption($params): PaymentOption
    {
        global $cookie;
        $cart = $params['cart'] ?? null;

        if (!$cart instanceof Cart) {
            throw new \RuntimeException('Cart is not available in payment option params.');
        }
        // Buyer details
        $customer = new Customer((int)($cart->id_customer));

        $toCurrency   = new Currency(Currency::getIdByIsoCode('ZAR'));
        $fromCurrency = new Currency((int)$cookie->id_currency);

        $total = $cart->getOrderTotal();

        $pfAmount = Tools::convertPriceFull($total, $fromCurrency, $toCurrency);

        $data = [];

        $currency = $this->getCurrency((int)$cart->id_currency);
        if ($cart->id_currency != $currency->id) {
            // If Payfast currency differs from local currency
            $cart->id_currency   = (int)$currency->id;
            $cookie->id_currency = $cart->id_currency;
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
        $data['info']['cancel_url']    = $this->context->link->getPageLink('cart', true, null, ['action' => 'show']);
        $data['info']['notify_url']    = $this->context->link->getModuleLink($this->name, 'validation', [], true);
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
        $payfastValues = $this->getPayfastValues($data['info']);

        //add selected split payment values
        if (Configuration::get('PAYFAST_SPLIT_PAYMENT_ENABLED')) {
            $data['info']['setup']['split_payment']['merchant_id'] = Configuration::get(
                'PAYFAST_SPLIT_PAYMENT_MERCHANT_ID'
            );
            $data['info']['setup']['split_payment']['amount']      = Configuration::get('PAYFAST_SPLIT_PAYMENT_AMOUNT');
            $data['info']['setup']['split_payment']['percentage']  = Configuration::get(
                'PAYFAST_SPLIT_PAYMENT_PERCENTAGE'
            );
            $data['info']['setup']['split_payment']['min']         = Configuration::get('PAYFAST_SPLIT_PAYMENT_MIN');
            $data['info']['setup']['split_payment']['max']         = Configuration::get('PAYFAST_SPLIT_PAYMENT_MAX');

            $split_payment_array    = array_filter($data['info']['setup']['split_payment'], function ($val) {
                return !empty($val);
            });
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

    public function hookDisplayPaymentReturn($params): string
    {
        if (!$this->active) {
            return '';
        }

        return $this->fetch('module:payfast/views/templates/front/confirmation.tpl');
    }


    private function displayLogoBlock($position): string
    {
        return '
            <div style="text-align:center;">
                <a href="https://payfast.io" target="_blank" rel="nofollow" title="Pay with Payfast">
                    <img src="' . __PS_BASE_URI__ . 'modules/payfast/payfast-logo.svg" style="width: 150px; height: auto;" />
                </a>
            </div>';
    }

    /**
     * Generate Payfast form values array
     *
     * @param array $paymentInfo Payment information array
     *
     * @return array[] Array of form field configurations
     */
    protected function getPayfastValues(array $paymentInfo): array
    {
        return [
            'merchant_id'   => [
                'name'  => 'merchant_id',
                'type'  => 'hidden',
                'value' => $paymentInfo['merchant_id'] ?? '',
            ],
            'merchant_key'  => [
                'name'  => 'merchant_key',
                'type'  => 'hidden',
                'value' => $paymentInfo['merchant_key'] ?? '',
            ],
            'return_url'    => [
                'name'  => 'return_url',
                'type'  => 'hidden',
                'value' => $paymentInfo['return_url'] ?? '',
            ],
            'cancel_url'    => [
                'name'  => 'cancel_url',
                'type'  => 'hidden',
                'value' => $paymentInfo['cancel_url'] ?? '',
            ],
            'notify_url'    => [
                'name'  => 'notify_url',
                'type'  => 'hidden',
                'value' => $paymentInfo['notify_url'] ?? '',
            ],
            'name_first'    => [
                'name'  => 'name_first',
                'type'  => 'hidden',
                'value' => $paymentInfo['name_first'] ?? '',
            ],
            'name_last'     => [
                'name'  => 'name_last',
                'type'  => 'hidden',
                'value' => $paymentInfo['name_last'] ?? '',
            ],
            'email_address' => [
                'name'  => 'email_address',
                'type'  => 'hidden',
                'value' => $paymentInfo['email_address'] ?? '',
            ],
            'm_payment_id'  => [
                'name'  => 'm_payment_id',
                'type'  => 'hidden',
                'value' => $paymentInfo['m_payment_id'] ?? '',
            ],
            'amount'        => [
                'name'  => 'amount',
                'type'  => 'hidden',
                'value' => $paymentInfo['amount'] ?? '0.00',
            ],
            'item_name'     => [
                'name'  => 'item_name',
                'type'  => 'hidden',
                'value' => $paymentInfo['item_name'] ?? '',
            ],
            'custom_int1'   => [
                'name'  => 'custom_int1',
                'type'  => 'hidden',
                'value' => $paymentInfo['custom_int1'] ?? 0,
            ],
            'custom_str1'   => [
                'name'  => 'custom_str1',
                'type'  => 'hidden',
                'value' => $paymentInfo['custom_str1'] ?? '',
            ],
            'custom_str2'   => [
                'name'  => 'custom_str2',
                'type'  => 'hidden',
                'value' => $paymentInfo['custom_str2'] ?? '',
            ],
        ];
    }
}
