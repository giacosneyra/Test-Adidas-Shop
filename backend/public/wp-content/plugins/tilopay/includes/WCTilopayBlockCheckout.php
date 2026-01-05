<?php

/**
 * Tilopay class extend from WC_Payment_Gateway, to process payment and update WOO order status.
 *
 * @package Tilopay
 */

namespace Tilopay;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Tilopay_Blocks extends AbstractPaymentMethodType {

    /**
     * The gateway instance.
     *
     * @var WCTilopay
     */
    private $gateway;

    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'tilopay'; // your payment gateway name

    /**
     * Initializes the payment method type.
     */
    public function initialize() {
        $this->settings = get_option('woocommerce_tilopay_settings', []);
        //Get all payment gateways
        $gateways       = WC()->payment_gateways->payment_gateways();
        //Get tilopay gateway
        if (isset($gateways[$this->name])) {
            $this->gateway = $gateways[$this->name];
        } else {
            $this->gateway = new WCTilopay();
        }
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active() {
        return $this->gateway->is_available();
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles() {
        $script_path       = '/assets/blocks/frontend/blocks.js'; //from where be generated from webpack
        $script_asset_path = PLUGIN_ABS_PATH_TPAY . '/assets/blocks/frontend/blocks.asset.php'; //from where be generated from webpack
        $script_asset      = file_exists($script_asset_path)
            ? require($script_asset_path)
            : array(
                'dependencies' => array(),
                'version'      => '3.0.0'
            );
        $script_url        = PLUGIN_URL_TPAY . $script_path;

        wp_register_script(
            'WCTilopayBlockCheckout',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('WCTilopayBlockCheckout', 'tilopay', PLUGIN_ABS_PATH_TPAY . 'languages/');
        }

        return ['WCTilopayBlockCheckout'];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data() {
        $gateWay_tilopay = $this->gateway;
        // Remove unended data
        unset($gateWay_tilopay->form_fields);
        unset($gateWay_tilopay->settings);
        unset($gateWay_tilopay->tpay_user);
        unset($gateWay_tilopay->tpay_password);
        //Set gateway tilopay
        $fe_params = get_object_vars($gateWay_tilopay);
        //Set nonce
        $fe_params['tpay_nonce'] = wp_create_nonce($this->name . '-tpay-woo-action-nonce');
        $fe_params['sdk_init_payload'] = (new WCTilopay())->setDataInit();

        return $fe_params;
    }
}
