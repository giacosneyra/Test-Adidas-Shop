<?php

/**
 * Tilopay hook init, validate and activated plugin.
 *
 * @package  Tilopay
 */

namespace Tilopay;

class InitTilopay {
	public static function initHooks() {

		$tilopay_helper = new TilopayHelper();

		// Add filter to allow redirect to Tilopay domain
		add_filter('allowed_redirect_hosts', array($tilopay_helper, 'tilopay_allowed_redirect_hosts'));

		//Link settings
		add_filter('plugin_action_links_' . TPAY_PLUGIN_NAME, array($tilopay_helper, 'settings_link'));

		register_activation_hook(__FILE__, array($tilopay_helper, 'tpay_plugin_cancel_tilopay'));

		//Check if tilpay payment is enable
		add_filter('woocommerce_available_payment_gateways', array($tilopay_helper, 'tilopay_gateway_payment_status'));

		add_action('tpay_my_cron_tilopay', array($tilopay_helper, 'tpay_my_process_tilopay'));

		add_filter('cron_schedules', array($tilopay_helper, 'tpay_add_cron_recurrence_interval'));

		//Is located at TilopayHelper, function tilopay_on_init
		add_action('plugins_loaded', array(new TilopayHelper(), 'tilopay_on_init'));
		//Is located at TilopayHelper, function load_tilopay_textdomain
		add_filter('load_textdomain_mofile', array(new TilopayHelper(), 'load_tilopay_textdomain'), 10, 2);

		// Registers WooCommerce Blocks integration.
		add_action('woocommerce_blocks_loaded', array($tilopay_helper, 'tilopay_woocommerce_gateway_block_support'));

		// Declaring extension (in)compatibility with WOO HPOS
		add_action('before_woocommerce_init', array($tilopay_helper, 'tilopay_gateway_high_performance_order_storage_support'));

		// Add block notice template to include close button handle by Tilopay
		add_filter('woocommerce_locate_template', array($tilopay_helper, 'tilopay_locate_block_notice_template'), 10, 3);

		do_action('woocommerce_set_cart_cookies', true);

		// Handle query params from Tilopay return
		add_action('init', array($tilopay_helper, 'handle_query_params_tilopay_return'), 12);

		/**
		 * Intercept if email was sent to set flag
		 */
		add_action('woocommerce_email_sent', array($tilopay_helper, 'tilopay_mark_order_email_sent'), 10, 3);
	}
}
