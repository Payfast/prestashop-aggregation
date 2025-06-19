<?php

/*
 * payfast.php
 *
 * Copyright (c) 2025 Payfast (Pty) Ltd
 *
 * @link       https://payfast.io/integration/plugins/prestashop/
 */

/**
 * @since 1.5.0
 */


use Payfast\PayfastCommon\Aggregator\Request\PaymentRequest;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once _PS_MODULE_DIR_ . 'payfast/payfast.php';

class PayfastValidationModuleFrontController extends ModuleFrontController
{
    public const REDIRECTBACK = 'index.php?controller=order&step=1';

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess(): void
    {
        // Variable Initialization
        $pfError       = false;
        $pfErrMsg      = '';
        $pfHost        = ((Configuration::get('PAYFAST_MODE') == 'live') ? 'www' : 'sandbox') . '.payfast.co.za';
        $pfParamString = '';

        $paymentRequest = new PaymentRequest(Configuration::get('PAYFAST_LOGS'));


        $paymentRequest->pflog('Payfast ITN call received');

        $paymentRequest->pflog('Get posted data');

        // Posted variables from ITN
        $pfData = $paymentRequest->pfGetData();

        $paymentRequest->pflog('Payfast Data: ' . print_r($pfData, true));

        if ($pfData === false) {
            $pfError  = true;
            $pfErrMsg = PaymentRequest::PF_ERR_BAD_ACCESS;
        }

        //// Verify security signature
        if (!$pfError) {
            $paymentRequest->pflog('Verify security signature');

            $passPhrase   = Configuration::get('PAYFAST_PASSPHRASE');
            $pfPassPhrase = empty($passPhrase) ? null : $passPhrase;

            // If signature different, log for debugging
            if (!$paymentRequest->pfValidSignature($pfData, $pfParamString, $pfPassPhrase)) {
                $pfError  = true;
                $pfErrMsg = PaymentRequest::PF_ERR_INVALID_SIGNATURE;
            }
        }

        $this->validatePayfastTransaction($pfError, $pfErrMsg, $pfData, $pfHost, $pfParamString);
    }

    public function validatePayfastTransaction($pfError, $pfErrMsg, $pfData, $pfHost, $pfParamString): void
    {
        $paymentRequest = new PaymentRequest(Configuration::get('PAYFAST_LOGS'));
        $moduleInfo     = [
            'pfSoftwareName'       => 'PrestaShop',
            'pfSoftwareVer'        => Configuration::get('PS_INSTALL_VERSION'),
            'pfSoftwareModuleName' => 'PF-Prestashop',
            'pfModuleVer'          => '1.3.0',
        ];
        //// Get internal cart
        if (!$pfError) {
            // Get order data
            $cart = new Cart((int)$pfData['m_payment_id']);
        }

        if (!$cart->id) {
            Tools::redirect(self::REDIRECTBACK);
        }

        //// Verify data received
        if (!$pfError) {
            $paymentRequest->pflog('Verify data received');

            $pfValid = $paymentRequest->pfValidData($moduleInfo, $pfHost, $pfParamString);

            if (!$pfValid) {
                $pfError  = true;
                $pfErrMsg = PaymentRequest::PF_ERR_BAD_ACCESS;
            }
        }

        //// Check data against internal order
        if (!$pfError) {
            $paymentRequest->pflog('Check data against internal order');
            $fromCurrency = new Currency(Currency::getIdByIsoCode('ZAR'));
            $toCurrency   = new Currency($cart->id_currency);

            $total = Tools::convertPriceFull($pfData['amount_gross'], $fromCurrency, $toCurrency);

            // Check order amount
            if (strcasecmp($pfData['custom_str2'], $cart->secure_key) != 0) {
                $pfError  = true;
                $pfErrMsg = PaymentRequest::PF_ERR_SESSIONID_MISMATCH;
            }
        }

        $this->processPayfastOrder($total, $pfError, $pfData, $pfErrMsg);
    }


    public function processPayfastOrder($total, $pfError, $pfData, $pfErrMsg): void
    {
        $paymentRequest = new PaymentRequest(Configuration::get('PAYFAST_LOGS'));

        //// Check status and update order
        if (!$pfError) {
            $paymentRequest->pflog('Check status and update order');

            $transaction_id = $pfData['pf_payment_id'];

            if (empty(Context::getContext()->link)) {
                Context::getContext()->link = new Link();
            }

            switch ($pfData['payment_status']) {
                case 'COMPLETE':
                    $paymentRequest->pflog('- Complete');

                    // Update the purchase status
                    $this->module->validateOrder(
                        (int)$pfData['custom_int1'],
                        _PS_OS_PAYMENT_,
                        (float)$total,
                        $this->module->displayName,
                        null,
                        array('transaction_id' => $transaction_id),
                        null,
                        false,
                        $pfData['custom_str2']
                    );

                    break;

                case 'FAILED':
                    $paymentRequest->pflog('- Failed');

                    // If payment fails, delete the purchase log
                    $this->module->validateOrder(
                        (int)$pfData['custom_int1'],
                        _PS_OS_ERROR_,
                        (float)$total,
                        $this->module->displayName,
                        null,
                        array('transaction_id' => $transaction_id),
                        null,
                        false,
                        $pfData['custom_str2']
                    );

                    break;

                case 'PENDING':
                    $paymentRequest->pflog('- Pending');

                    // Need to wait for "Completed" before processing
                    break;

                default:
                    // If unknown status, do nothing (safest course of action)
                    break;
            }
        }

        // If an error occurred
        if ($pfError) {
            $paymentRequest->pflog('Error occurred: ' . $pfErrMsg);
        }

        // Close log
        $paymentRequest->pflog('', true);
        exit;
    }
}
