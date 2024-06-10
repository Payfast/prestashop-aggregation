<?php

/*
 * payfast.php
 *
 * Copyright (c) 2024 Payfast (Pty) Ltd
 *
 * @author     App Inlet
 * @version    1.2.2
 * @date       2024/06/10
 *
 * @link       https://payfast.io/integration/plugins/prestashop/
 */

/**
 * @since 1.5.0
 */


use Payfast\PayfastCommon\PayfastCommon;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once _PS_MODULE_DIR_ . 'payfast/payfast.php';

class PayfastValidationModuleFrontController extends ModuleFrontController
{
    public const REDIRECTBACK = 'index.php?controller=order&step=1';

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        // Variable Initialization
        $pfError       = false;
        $pfErrMsg      = '';
        $pfDone        = false;
        $pfData        = array();
        $pfHost        = ((Configuration::get('PAYFAST_MODE') == 'live') ? 'www' : 'sandbox') . '.payfast.co.za';
        $pfParamString = '';


        PayfastCommon::pflog('Payfast ITN call received');

        //// Notify Payfast that information has been received
        header('HTTP/1.0 200 OK');
        flush();

        //// Get data sent by Payfast
        if (!$pfError && !$pfDone) {
            PayfastCommon::pflog('Get posted data');

            // Posted variables from ITN
            $pfData = PayfastCommon::pfGetData();

            PayfastCommon::pflog('Payfast Data: ' . print_r($pfData, true));

            if ($pfData === false) {
                $pfError  = true;
                $pfErrMsg = PayfastCommon::PF_ERR_BAD_ACCESS;
            }
        }

        //// Verify security signature
        if (!$pfError && !$pfDone) {
            PayfastCommon::pflog('Verify security signature');

            $passPhrase   = Configuration::get('PAYFAST_PASSPHRASE');
            $pfPassPhrase = empty($passPhrase) ? null : $passPhrase;

            // If signature different, log for debugging
            if (!PayfastCommon::pfValidSignature($pfData, $pfParamString, $pfPassPhrase)) {
                $pfError  = true;
                $pfErrMsg = PayfastCommon::PF_ERR_INVALID_SIGNATURE;
            }
        }

        $this->postProcessStepB($pfError, $pfDone, $pfErrMsg, $pfData, $pfHost, $pfParamString);
    }

    public function postProcessStepB($pfError, $pfDone, $pfErrMsg, $pfData, $pfHost, $pfParamString)
    {
        //// Get internal cart
        if (!$pfError && !$pfDone) {
            // Get order data
            $cart = new Cart((int)$pfData['m_payment_id']);
        }

        if (!$cart->id) {
            Tools::redirect(self::REDIRECTBACK);
        }

        //// Verify data received
        if (!$pfError) {
            PayfastCommon::pflog('Verify data received');

            $pfValid = PayfastCommon::pfValidData($pfHost, $pfParamString);

            if (!$pfValid) {
                $pfError  = true;
                $pfErrMsg = PayfastCommon::PF_ERR_BAD_ACCESS;
            }
        }

        //// Check data against internal order
        if (!$pfError && !$pfDone) {
            PayfastCommon::pflog('Check data against internal order');
            $fromCurrency = new Currency(Currency::getIdByIsoCode('ZAR'));
            $toCurrency   = new Currency((int)$cart->id_currency);

            $total = Tools::convertPriceFull($pfData['amount_gross'], $fromCurrency, $toCurrency);

            // Check order amount
            if (strcasecmp($pfData['custom_str2'], $cart->secure_key) != 0) {
                $pfError  = true;
                $pfErrMsg = PayfastCommon::PF_ERR_SESSIONID_MISMATCH;
            }
        }

        $this->postProcessStepC($total, $pfError, $pfDone, $pfData, $pfErrMsg);
    }

    public function postProcessStepC($total, $pfError, $pfDone, $pfData, $pfErrMsg)
    {
        //// Check status and update order
        if (!$pfError && !$pfDone) {
            PayfastCommon::pflog('Check status and update order');

            $transaction_id = $pfData['pf_payment_id'];

            if (empty(Context::getContext()->link)) {
                Context::getContext()->link = new Link();
            }

            switch ($pfData['payment_status']) {
                case 'COMPLETE':
                    PayfastCommon::pflog('- Complete');

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
                    PayfastCommon::pflog('- Failed');

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
                    PayfastCommon::pflog('- Pending');

                    // Need to wait for "Completed" before processing
                    break;

                default:
                    // If unknown status, do nothing (safest course of action)
                    break;
            }
        }

        // If an error occurred
        if ($pfError) {
            PayfastCommon::pflog('Error occurred: ' . $pfErrMsg);
        }

        // Close log
        PayfastCommon::pflog('', true);
        exit;
    }
}
