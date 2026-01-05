<?php

/**
 * Plugin to conecte Tilopay payment services.
 *
 * @package  Tilopay
 */

//Function helpers hooks
use Tilopay\TilopayHelper;

/*
 * Plugin Name: Tilopay
 * Plugin URI: https://wordpress.org/plugins/tilopay/
 * Description: Accept credit and debit cards on your WooCommerce Store
 * Version: 3.1.2
 * Requires Plugins: woocommerce
 * Author:  Tilopay
 * Author URI: https://tilopay.com
 * WC requires at least: 8.0.0
 * WC tested up to: 10.1.2
 * Tested up to: 6.8.2
 * License: GPLv2
 * Text Domain: tilopay
 * Domain Path: /languages
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
	exit;
}

define('TPAY_PLUGIN_VERSION', '3.1.2'); //set this same from changelog
define('TPAY_PLUGIN_DIR', __DIR__);
define('TPAY_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('TPAY_PLUGIN_URL', plugins_url('/', __FILE__));
define('TPAY_PLUGIN_NAME', plugin_basename(dirname(__FILE__)) . '/tilopay.php');
define('TPAY_BASE_URL', 'https://app.tilopay.com/');
define('TPAY_ENV_URL', TPAY_BASE_URL . 'api/v1/');
define('TPAY_SDK_URL', TPAY_BASE_URL . 'sdk/v2/sdk_tpay.min.js');
define('PLUGIN_ABS_PATH_TPAY', trailingslashit(plugin_dir_path(__FILE__)));
define('PLUGIN_URL_TPAY', untrailingslashit(plugins_url('/', __FILE__)));
define('TPAY_MAIN_FILE', __FILE__);

if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
	require_once dirname(__FILE__) . '/vendor/autoload.php';
}

// Ensure WooCommerce is active on single site or multisite
if (
    !in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) &&
    !array_key_exists('woocommerce/woocommerce.php', apply_filters('active_sitewide_plugins', get_site_option('active_sitewide_plugins', [])))
) {
    return;
}

//Woocommerce init
function tpay_woocommerce_init_tilopay_gateway() {
	if (!class_exists('WC_Payment_Gateway')) {
		return;
	}

	$tilopay_helper_hook = new TilopayHelper();

	//Register Tilopay Gateway class
	add_filter('woocommerce_payment_gateways', array($tilopay_helper_hook, 'add_woocommerce_tilopay_gateway'));

	add_filter('woocommerce_after_checkout_form', array($tilopay_helper_hook, 'tpay_filter_woocommerce_credit_card_form_fields'));

	add_action('woocommerce_order_status_changed', array($tilopay_helper_hook, 'tpay_order_status_changed'));

	/*
	// add the action
	add_action( 'woocommerce_order_partially_refunded', array( $tilopay_helper_hook, 'tpay_woocommerce_order_partially_refunded' ), 10, 2 );

	// add the action
	add_action( 'woocommerce_order_fully_refunded', array( $tilopay_helper_hook, 'tpay_woocommerce_order_fully_refunded' ), 10, 2 );

	*/
	// add the action
	add_action('woocommerce_order_refunded', array($tilopay_helper_hook, 'tpay_woocommerce_order_refunded'), 10, 2);

	/**
	 * Hook front script
	 * is located at TilopayHelper, function load_tilopay_front_scripts
	 */
	add_action('wp_enqueue_scripts', array($tilopay_helper_hook, 'load_tilopay_front_scripts'), 9999);

	/**
	 * Admin script to upload logo, only load at WC wc-settings page
	 * is located at TilopayHelper, function enqueuing_admin_config_payment_scripts
	 */
	add_action('admin_enqueue_scripts', array($tilopay_helper_hook, 'enqueuing_admin_config_payment_scripts'));

	/**
	 * Add at sub menu woocommerce
	 */
	//add_action('admin_menu', array($tilopay_helper_hook, 'tpay_add_menu'));

	/**
	 * For FE call form validation
	 */
	add_action('rest_api_init', array($tilopay_helper_hook, 'register_tilopay_validation_form_route'));

}
//check if load woocommerce_init | woocommerce_loaded
add_action('woocommerce_loaded', 'tpay_woocommerce_init_tilopay_gateway');

//Init hooks
if (class_exists('Tilopay\\InitTilopay')) {
	\Tilopay\InitTilopay::initHooks();
}
