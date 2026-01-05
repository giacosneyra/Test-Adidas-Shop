<?php

/**
 * We load support payment and settings input.
 *
 * @package Tilopay
 */

namespace Tilopay;

use Automattic\WooCommerce\Utilities\OrderUtil;

class TilopayConfig {

	/**
	 * Supported payment options for Tilopay.
	 *
	 * @return array
	 */
	public static function tilopay_supported_payment_options() {
		//Payment options allowed
		return array(
			'products',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change',
			'subscription_payment_method_change_admin'
		);
	}

	/**
	 * Returns an array of form fields to configure TILOPAY.
	 *
	 * @return array.
	 */
	public static function formConfigFields() {
		//Form fields to config TILOPAY
		return array(
			'enabled' => array(
				'title' => __('Enable/Disable', 'tilopay'),
				'label' => __('Enable TILOPAY', 'tilopay'),
				'type' => 'checkbox',
				'description' => '',
				'default' => 'no'
			),
			'title' => array(
				'title' => __('Title', 'tilopay'),
				'type' => 'text',
				'description' => __('Title to be displayed in the payment methods', 'tilopay') . ', <a href="' . esc_url('https://tilopay.com/documentacion/plataforma-woocommerce') .'" target="_blank" rel="noopener noreferrer">' . __('Guide to setting up WooCommerce.', 'tilopay') . '</a>',
				'default' => 'Tilopay',
				'placeholder' => __('Pay with', 'tilopay') . ' Tilopay',
			),
			// 'icon' => array(
			// 	'title' => __('Icon', 'tilopay'),
			// 	'type' => 'hidden',
			// 	'description' => __('Click on the image to change the payment method icon.', 'tilopay'),
			// 	'default' => '',
			// ),
			'tpay_key' => array(
				'title' => __('Integration key', 'tilopay'),
				'type' => 'text',
				'description' => __('This field is where you must enter the integration API key generated through the Tilopay user portal when associating a payment method to the integration.', 'tilopay'). ' <a href="' . esc_url('https://admin.tilopay.com/admin/checkout') .'" target="_blank" rel="noopener noreferrer">' . __('View my API credentials.', 'tilopay') . '</a>',
				'placeholder' => __('Integration key', 'tilopay') . ' 0000-0000-0000-0000-0000',
				'default' => ''
			),
			'tpay_user' => array(
				'title' => __('API user', 'tilopay'),
				'type' => 'text',
				'description' => __('This field is where you must enter the integration API user generated through the Tilopay user portal when associating a payment method to the integration.', 'tilopay'). ' <a href="' . esc_url('https://admin.tilopay.com/admin/checkout') .'" target="_blank" rel="noopener noreferrer">' . __('View my API credentials.', 'tilopay') . '</a>',
				'placeholder' => __('API user', 'tilopay'),
				'default' => ''
			),
			'tpay_password' => array(
				'title' => __('API password', 'tilopay'),
				'type' => 'text',
				'description' => __('This field is where you must enter the integration API password generated through the Tilopay user portal when associating a payment method to the integration.', 'tilopay'). ' <a href="' . esc_url('https://admin.tilopay.com/admin/checkout') .'" target="_blank" rel="noopener noreferrer">' . __('View my API credentials.', 'tilopay') . '</a>',
				'placeholder' => __('API password', 'tilopay'),
				'default' => ''
			),
			'tpay_capture' => array(
				'title' => __('Immediate capture', 'tilopay'),
				'type' => 'select',
				'options' => array('yes' => __('Yes, capture', 'tilopay'), 'no' => __('Do not capture', 'tilopay')),
				'description' => __('Select no, if you require authorization without capture, the orders will be in Pending payment status. To capture, the order status must be changed to Processing. Maximum date to capture: 7 days after authorized, after 7 days the collection is automatically canceled', 'tilopay'),
				'default' => 'yes'
			),
			'tpay_capture_yes' => array(
				'title' => __('Order Status', 'tilopay'),
				'type' => 'select',
				'options' => array('processing' => __('Processing', 'tilopay'), 'completed' => __('Completed', 'tilopay')),
				'description' => __('Select the order payment status', 'tilopay'),
				'default' => 'processing'
			),
			'tpay_logo_options' => array(
				'title' => __('Set up logos', 'tilopay'),
				'description' => __('Select which logos to show, you can show all of them or select which ones you prefer.', 'tilopay'),
				'type' => 'multiselect',
				//'default' => 'visa',
				//'class' => 'msf_multiselect_container',
				//'css' => 'CSS rules added line to the input',
				//'label' => 'Label', // checkbox only
				'options' => array(
					'visa' => 'Visa',
					'mastercard' => 'Mastercard',
					'american_express' => 'American Express',
					'sinpemovil' => 'Sinpemóvil',
					//'credix' => 'Credix',//uncommetn when is ready
					'sistema_clave' => 'Sistema Clave',
					'mini_cuotas' => 'Minicuotas',
					'tasa_cero' => 'Tasa Cero',
				) // array of options for select/multiselects only
			),
			'tpay_redirect' => array(
				'title' => __('Embedded native payment or through redirect', 'tilopay'),
				'type' => 'select',
				'options' => array('yes' => __('Redirect to payment form', 'tilopay'), 'no' => __('Native checkout payment form', 'tilopay')),
				'description' => __('Select if you want to redirect the user to process the payment or use native checkout payment', 'tilopay') . ', <a href="' . esc_url('https://tilopay.com/documentacion/plataforma-woocommerce') .'" target="_blank" rel="noopener noreferrer">' . __('Guide to setting up WooCommerce.', 'tilopay') . '</a>',
				'default' => 'yes'
			),
			'debug_mode' => array(
				'title' => __('Enable/Disable', 'tilopay'),
				'label' => __('Enable debugging mode', 'tilopay'),
				'type' => 'checkbox',
				'description' => __('When debug mode is enabled, logs named tilopay will be created,', 'tilopay') . ' <a href="' . esc_url( get_site_url() . '/wp-admin/admin.php?page=wc-status&tab=logs') .'">' . __('View logs.', 'tilopay') . '</a>',
				'default' => 'no'
			),
		);
	}

	/**
	 * Check if the HPOS (High Performance Order System) is active.
	 *
	 * @return bool Returns true if the HPOS is active, false otherwise.
	 */
	public static function tilopay_check_is_active_HPOS() {
		return OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * Check if the checkout block is being used.
	 *
	 * @return bool Returns true if the checkout block is being used, false otherwise.
	 */
	public static function tilopay_check_is_checkout_block() {
		return \WC_Blocks_Utils::has_block_in_page(wc_get_page_id('checkout'), 'woocommerce/checkout');
	}

	/**
	 * Display block notices based on the notice type and message.
	 *
	 * @param string $notice_type The type of notice to display.
	 * @param string $message The message to display.
	 */
	public static function tilopay_show_block_notices($notice_type, $message) {
		wc_get_template(
			"block-notices/{$notice_type}.php",
			array(
				//'messages' => array( $message ), // @deprecated 3.9.0
				'notices'  => array(
					array(
						'notice' => $message,
						'data'   => array(),
					),
				),
			)
		);
	}
}
