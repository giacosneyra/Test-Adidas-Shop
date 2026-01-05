<?php

/**
 * Tilopay class extend from WC_Payment_Gateway, to process payment and update WOO order status.
 *
 * @package Tilopay
 */

namespace Tilopay;

use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Customer;

if (!defined('ABSPATH')) {
	exit;
}

class WCTilopay extends \WC_Payment_Gateway {

	public $site_url;
	public $is_active_HPOS = false;
	public $have_subscription;
	public $is_native_method = false;
	public $is_checkout_block = false;
	public $id = 'tilopay'; //Do not change to uppercase
	public $method_title = 'Tilopay';
	public $method_description = 'Tilopay.';
	public $tpay_checkout_redirect;
	public $tpay_key;
	public $tpay_mini_cuota;
	public $tpay_tasa_cero;
	public $tpay_user;
	public $tpay_password;
	public $tpay_capture;
	public $tpay_capture_yes;
	public $tpay_logo_options;
	public $tpay_redirect;
	public $availability;
	public $approve_payment = false;
	private $log;
	public $hs;
	public $nativePaymentMethod = false;
	public $maxRetries = 3;
	public $retryCount = 0;
	public $debug_mode = 'no';
	protected $tilopay_getaway_settings = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wc_currency;
		$this->id = 'tilopay';
		$this->method_title = __('TILOPAY', 'tilopay');
		$this->method_description = __('TILOPAY.', 'tilopay');
		// Load the settings
		$this->init_settings();

		// User defined settings
		$this->availability = 'all';
		$this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'no';
		$this->title = isset($this->settings['title']) ? $this->settings['title'] : 'Tilopay';
		$this->icon = isset($this->settings['icon']) ? $this->settings['icon'] : '';
		$this->tpay_checkout_redirect = wc_get_checkout_url();
		$this->tpay_key = isset($this->settings['tpay_key']) ? $this->settings['tpay_key'] : '';
		$this->tpay_mini_cuota = isset($this->settings['tpay_mini_cuota']) ? $this->settings['tpay_mini_cuota'] : '';
		$this->tpay_tasa_cero = isset($this->settings['tpay_tasa_cero']) ? $this->settings['tpay_tasa_cero'] : '';
		$this->tpay_user = isset($this->settings['tpay_user']) ? $this->settings['tpay_user'] : '';
		$this->tpay_password = isset($this->settings['tpay_password']) ? $this->settings['tpay_password'] : '';
		$this->tpay_capture = isset($this->settings['tpay_capture']) ? $this->settings['tpay_capture'] : 'yes';
		$this->tpay_capture_yes = isset($this->settings['tpay_capture_yes']) ? $this->settings['tpay_capture_yes'] : 'processing';
		$this->tpay_logo_options = isset($this->settings['tpay_logo_options']) ? $this->settings['tpay_logo_options'] : []; //array
		$this->tpay_redirect = isset($this->settings['tpay_redirect']) ? $this->settings['tpay_redirect'] : 'no';
		$this->init_form_fields();
		$this->site_url = esc_url(home_url('/'));
		$this->debug_mode = isset($this->settings['debug_mode']) ? $this->settings['debug_mode'] : 'no';

		//$this->tpay_redirect == 'no' = Native | 'yes' = Redirect
		$this->is_native_method = false;
		if ('yes' == $this->tpay_redirect && isset($this->tpay_redirect)) {
			$this->is_native_method = false;
		} else {
			$this->has_fields = true; //Direct Gateways
			$this->is_native_method = true;
		}

		// HPOS usage is enabled.
		$this->is_active_HPOS = TilopayConfig::tilopay_check_is_active_HPOS();

		// Checkout block is enabled.
		$this->is_checkout_block = TilopayConfig::tilopay_check_is_checkout_block();

		//Pyament supported with tilopay
		$this->supports = \Tilopay\TilopayConfig::tilopay_supported_payment_options();

		// Set the Tilopay settings
		$this->tilopay_getaway_settings =  [
			'id' => $this->id,
			'method_title' => $this->method_title,
			'description' => $this->method_description,
			'availability' => $this->availability,
			'enabled' => $this->enabled,
			'title' => $this->title,
			'icon' => $this->icon,
			'tpay_checkout_redirect' => $this->tpay_checkout_redirect,
			'tpay_key' => $this->tpay_key,
			'tpay_mini_cuota' => $this->tpay_mini_cuota,
			'tpay_tasa_cero' => $this->tpay_tasa_cero,
			'tpay_user' => $this->tpay_user,
			'tpay_password' => $this->tpay_password,
			'capture' => $this->tpay_capture,
			'capture_yes' => $this->tpay_capture_yes,
			'tpay_logo_options' => $this->tpay_logo_options,
			'tpay_redirect' => $this->tpay_redirect,
			'site_url' => $this->site_url,
			'debug_mode' => $this->debug_mode,
			'is_native_method' => $this->is_native_method,
			'is_active_HPOS' => $this->is_active_HPOS,
			'is_checkout_block' => $this->is_checkout_block,
		];

		$this->have_subscription = false;
		add_filter('woocommerce_rest_api_enabled', '__return_false');
		add_action('woocommerce_scheduled_subscription_payment_retry', array($this, 'tpay_retry_subscription_order'), 1, 1);
		add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'tpay_scheduled_subscription_payment'), 10, 2);
		add_action('woocommerce_subscriptions_changed_failing_payment_method_' . $this->id, array($this, 'failing_payment_method'), 10, 2);

		// Hooks
		add_action('admin_notices', array($this, 'admin_notices'));
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

		//webhook to update order from tilopay( /?wc-api=tilopay_response_woo )
		add_action('woocommerce_api_tilopay_response_woo', array($this, 'tpay_payment_webhook_process'));

		if ($this->is_native_method) {
			// We need custom JavaScript to obtain a token
			add_action('wp_enqueue_scripts', array($this, 'tpay_payment_scripts'), 9998);
		}

		add_action('wp_enqueue_scripts', array($this, 'tpay_css_front'), 9998);
	}

	function tpay_url_for_invalide_hash_confirmation($order, $computed_hash_hmac_tilopay_server, $customer_computed_hash_hmac, $wc_order_id) {

		// translators: %s the order number.
		$order->add_order_note(__('Description:', 'tilopay') . sprintf(__('Invalid order confirmation, please check and try again or contact the seller to completed you order no.%s', 'tilopay'), $wc_order_id));
		$order->add_order_note('Order hash:', ' tpay:' . sanitize_text_field($computed_hash_hmac_tilopay_server) . '|website:' . sanitize_text_field($customer_computed_hash_hmac));
		// translators: %s the order number.
		TilopayConfig::tilopay_show_block_notices('error', 'Error: ' . sprintf(__('Invalid order confirmation, please check and try again or contact the seller to completed you order no.%s', 'tilopay'), $wc_order_id));

		//Update order status
		$order->set_status('wc-failed');
		$order->save();

		// translators: %s the order number.
		$error_message_validation = sprintf(__('Invalid order confirmation, please check and try again or contact the seller to completed you order no.%s', 'tilopay'), $wc_order_id);

		$this->log(__METHOD__ . ':::' . __LINE__ . ', order_id:' . $wc_order_id . ', error message:' . $error_message_validation, 'info');

		$checkout_url = wc_get_checkout_url();
		$pos = strpos($checkout_url, '?');
		if (false === $pos) {
			$checkout_url = $checkout_url . '?message_error=' . $error_message_validation;
		} else {
			$checkout_url = $checkout_url . '&message_error=' . $error_message_validation;
		}

		return $checkout_url;
	}

	function tpay_apply_redirect_to_payment_form($show_error = false) {
		if (isset($_REQUEST['process_payment']) && 'tilopay' == $_REQUEST['process_payment'] && sanitize_text_field(isset($_REQUEST['tlpy_payment_order']))) {
			$order_id = sanitize_text_field($_REQUEST['tlpy_payment_order']);

			if ($this->is_active_HPOS) {
				// HPOS
				$getOrder = wc_get_order($order_id);
				$tpay_url_payment_form = $getOrder->get_meta('tilopay_html_form', true);
			} else {
				$tpay_url_payment_form = get_post_meta($order_id, 'tilopay_html_form')[0];
			}
			if (true === $show_error) {
				$tpay_url_payment_form = (false === strpos($tpay_url_payment_form, '?'))
					? $tpay_url_payment_form . '?paymentError=true'
					: $tpay_url_payment_form . '&paymentError=true';
			}

			//check if have html
			if ('' != isset($tpay_url_payment_form) && $tpay_url_payment_form) {

				$payment_form_tilopay = esc_url($tpay_url_payment_form);
				$granted_domain = false;
				if (false !== strpos($payment_form_tilopay, 'tilopay-staging-kb4ui2z5cq-uc.a.run.app')) {
					$granted_domain = true;
				}
				if (false !== strpos($payment_form_tilopay, 'staging--incredible-melomakarona-d1c124.netlify.app')) {
					$granted_domain = true;
				}
				if (false !== strpos($payment_form_tilopay, 'tilopay.com')) {
					$granted_domain = true;
				}

				if ($granted_domain) {
					$redirect_url = esc_url($payment_form_tilopay);
					wp_safe_redirect(str_replace('&#038;', '&', $redirect_url));
					exit;
				} else {
					$cross_domain_handle = (false === strpos($this->site_url, '?'))
						? $this->site_url . '?message_error=Cross domain redirect error, is not Tilopay redirect payment form.' . $tpay_url_payment_form . '&from=' . __LINE__
						: $this->site_url . '&message_error=Cross domain redirect error, is not Tilopay redirect payment form.' . $tpay_url_payment_form . '&from=' . __LINE__;
					// translators: %s the order number.
					TilopayConfig::tilopay_show_block_notices('error', 'Error: Cross domain redirect error, is not Tilopay redirect payment form.' . $tpay_url_payment_form);
					wp_safe_redirect(esc_url($cross_domain_handle));
					exit;
				}
			}
		}
	}

	/**
	 * Hash customer
	 */
	public function computed_customer_hash($external_orden_id, $amount, $currency, $tpay_order_id, $responseCode, $auth, $email) {
		$hashKey = $tpay_order_id . '|' . $this->tpay_key . '|' . $this->tpay_password;
		$params = [
			'api_Key' => $this->tpay_key,
			'api_user' => $this->tpay_user,
			'orderId' => $tpay_order_id,
			'external_orden_id' => $external_orden_id,
			'amount' => number_format($amount, 2),
			'currency' => $currency,
			'responseCode' => $responseCode,
			'auth' => $auth,
			'email' => $email
		];

		$log_params = $params;
		if (isset($params['api_user'])) {
			$log_params['api_user'] = '***';
		}

		//computed customer hash_hmac
		$own_hash = hash_hmac('sha256', http_build_query($params), $hashKey);
		$this->log(__METHOD__ . ':::' . ' own_hash:' . $own_hash . ', params:' . print_r($log_params, true), 'info');
		//computed customer hash
		return $own_hash;
	}
	/**
	 * Notify of issues in wp-admin
	 */
	public function admin_notices() {
		if ('no' == $this->enabled) {
			return;
		}
	}


	/**
	 * Logging method
	 *
	 * @paramstring $message
	 *
	 * @return void
	 */
	public function log($message, $level = 'notice') {
		if (!class_exists('WC_Logger')) {
			return;
		}

		if (empty($this->log)) {
			$this->log = new \WC_Logger();
		}

		//Only log if debug mode is yes
		if ('yes' == $this->debug_mode) {

			$this->log->add($this->id, $message, $level);
		}
	}

	/**
	 * Check if the gateway is available for use
	 *
	 * @return bool
	 */
	public function is_available() {
		$is_available = false;
		try {
			$is_available = parent::is_available();

			// Only allow unencrypted connections when testing
			if (!is_ssl()) {
				$is_available = false;
			}
		} catch (\Throwable $th) {
			//throw $th;
			$this->log(__METHOD__ . ':::' . __LINE__ . ' message_error:' . $th->getMessage() . ', file:' . $th->getFile() . ', line:' . $th->getLine(), 'error');
		}
		return $is_available;
	}

	/**
	 * The tpay_scheduled_subscription_payment help to process payment.
	 *
	 * @param $amount_to_charge float The amount to charge.
	 * @param $renewal_order WC_Order A WC_Order object created to record the renewal payment.
	 */
	public function tpay_scheduled_subscription_payment($amount_to_charge, $renewal_order) {
		$this->tpay_process_subscription($renewal_order, 'tpay_scheduled_subscription_payment');
	}


	/**
	 * Process the subscription payments.
	 *
	 * @param mixed $order WC_Order A WC_Order object created or post ID of the order to record the renewal payment.
	 * @param string $callFrom The function who call this method.
	 *
	 * @return void
	 */
	public function tpay_process_subscription($order, $callFrom = 'NA') {

		$renewal_order = wc_get_order($order);

		$subscriptions_ids = wcs_get_subscriptions_for_renewal_order($renewal_order);
		$subscription_id = 0;
		foreach ($subscriptions_ids as $order_subscription_id => $subscription_obj) {
			$subscription_id = $order_subscription_id;
			//example array
			// $subscriptions_ids = ["5277" => [
			// 	"order_type" => "shop_subscription",
			//     "refunds" => null
			//   ]
			// ]
			break;
		}

		$check_unpaid_status = (!in_array($renewal_order->get_status(), ['wc-processing', 'processing', 'refunded', 'wc-refunded', 'completed', 'wc-completed', 'cancelled', 'wc-cancelled']));

		$this->log(__METHOD__ . ':::' . __LINE__ . ', check callFrom:' . $callFrom . ' renewal_order:' . $renewal_order->get_status() . ' check_unpaid_status:' . ($check_unpaid_status ? 'true' : 'false'), 'info');
		$card = '';
		$authorization_number = '';
		if ($this->is_active_HPOS) {
			// HPOS
			$card = $renewal_order->get_meta('card', true);
			$authorization_number = $renewal_order->get_meta('authorization_number', true);
		} else {
			$card = get_post_meta($subscription_id, 'card')[0];
			$authorization_number = get_post_meta($subscription_id, 'authorization_number')[0];
		}

		if ('' === $card) {
			$wc_order_id = $renewal_order->get_id();

			$renewal_order->add_order_note(__('Error: The subscription does not have an associated card.', 'tilopay'));


			//Update order status
			$renewal_order->set_status('wc-failed');
			$renewal_order->save();

			return false;
		} else if ($check_unpaid_status) {
			$this->tpay_pay_order_with_token($renewal_order, $card, 'tpay_process_subscription');
		}
		return;
	}

	/**
	 * Tpay_pay_order_with_token function.
	 * Process the subscription payment with the card token.
	 *
	 * @param object $order Order object from the buyer.
	 * @param string $token Card token to pay the order subscription
	 * @return boolean
	 */
	public function tpay_pay_order_with_token($order, $token, $callFrom = 'NA') {

		$order = wc_get_order($order);
		$wc_order_id = $order->get_id();

		$this->log(__METHOD__ . ':::' . __LINE__ . ', check callFrom:' . $callFrom . ' renewal_order:' . $order->get_status(), 'info');

		$check_unpaid_status = (in_array($order->get_status(), ['wc-processing', 'processing', 'refunded', 'wc-refunded', 'completed', 'wc-completed', 'cancelled', 'wc-cancelled']));

		// Check if order has a status that already closed like processing mean that the payment was already done
		if ($check_unpaid_status) {
			$this->log(__METHOD__ . ':::' . __LINE__ . ', already paid, callFrom:' . $callFrom . ' renewal_order:' . $order->get_status(), 'info');
			return;
		}

		$headers = array(
			'Accept' => 'application/json',
			'Content-Type' => 'application/json',
			'Accept-Language' => get_bloginfo('language')
		);
		$datajson = [
			'email' => $this->tpay_user,
			'password' => $this->tpay_password
		];

		$body = wp_json_encode($datajson);

		$args = array(
			'body' => $body,
			'timeout' => '300',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => $headers,
			'cookies' => array(),
		);


		$response = wp_remote_post(TPAY_ENV_URL . 'login', $args);
		$result = json_decode($response['body']);

		$order_total = $order->get_total();

		if ('' === $token) {
			$order->add_order_note(__('Processing recurring payments requires a card token, recurrence does not have a card token.', 'tilopay'));

			//Update order status
			$order->set_status('wc-failed');
			$order->save();

			return false;
		}

		if ('' === $this->tpay_key) {

			$order->add_order_note(__("There seems to be an issue with this website's integration with Tilopay, please inform the seller to complete your purchase.", 'tilopay'));

			//Update order status
			$order->set_status('wc-failed');
			$order->save();

			return false;
		}

		// 'key' => 'required',
		// 'card' => 'required',
		// 'currency' => 'required',
		//  'amount' => 'required',
		//  'orderNumber' => 'required',
		//  'capture' => 'required',
		//  'email' => 'required|email',

		$dataJson = [
			'redirect' => $this->tpay_checkout_redirect,
			'key' => $this->tpay_key,
			'amount' => $order_total,
			'currency' => get_woocommerce_currency(),
			'email' => $order->get_billing_email(),
			'orderNumber' => $wc_order_id,
			'capture' => 'yes' == $this->tpay_capture ? 1 : 0,
			'card' => $token,
			'hashVersion' => 'V2',
			'callFrom' => 'Plugin woo',
			'language' => get_bloginfo('language'),
		];

		//Check if have a token
		if (isset($result->access_token)) {
			# Have token
			$headers = array(
				'Authorization' => 'bearer ' . $result->access_token,
				'Content-type' => 'application/json',
				'Accept' => 'application/json',
				'Accept-Language' => get_bloginfo('language')
			);

			$body = wp_json_encode($dataJson);
			$args = array(
				'body' => $body,
				'timeout' => '300',
				'redirection' => '5',
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => $headers,
				'cookies' => array(),
			);
			$response = wp_remote_post(TPAY_ENV_URL . 'processRecurrentPayment', $args);

			$result = json_decode($response['body']);

			if (is_wp_error($response)) {
				$cleanBodyRequest = $dataJson;
				$cleanBodyRequest['card'] = '...';
				$responseBodyError = isset($response['body']) ? $response['body'] : '';
				$this->log(__METHOD__ . ':::' . __LINE__ . ', response:' . print_r($responseBodyError, true) . ', request_data:' . print_r($cleanBodyRequest, true), 'error');
			}


			if (!empty($result)) {
				//process Tilopay Response
				$this->tpay_process_response($result, $order);
				return true;
			} else {

				$order->add_order_note(json_encode($result));
				$order->add_order_note(__('Connection error with TILOPAY, contact sac@tilopay.com.', 'tilopay'));

				//Update order status
				$order->set_status('wc-failed');
				$order->save();

				return false;
			}
		} else {

			//Dont have token
			$order->add_order_note(json_encode($result) . ' Pay With token ' . __LINE__);
			$order->add_order_note(__("There seems to be an issue with this website's integration with Tilopay, please inform the seller to complete your purchase.", 'tilopay') . ' Pay With token ' . __LINE__);

			//Update order status
			$order->set_status('wc-failed');
			$order->save();

			return false;
		} //.End check have token

		return;
	}


	/**
	 * Process_response function
	 * Store extra meta data for an order from a TYP Response.
	 *
	 * @since 2.3.0
	 * @return object
	 */
	public function tpay_process_response($response, $order) {

		$wc_order_id = $order->get_id();
		$user_id = $order->get_user_id();

		/**
		 * Validate body
		 * json decode
		 * $response = [
		 * "type": "200",
		 * "response" => "1" || "0",
		 * "description" => "some text",
		 * "auth": "123456"
		 * ]
		 *
		 * $response->response == 1 aprove by Tilopay || 0 Payment failed
		 */

		if ('200' == $response->type && 1 == $response->response) {

			$textOrderTilopay = ('yes' == $this->tpay_capture) ? __('Capture', 'tilopay') : __('Authorization', 'tilopay');
			$tpay_order_id = (isset($response->order_id)) ? $response->order_id : '';

			if ($this->is_active_HPOS) {
				//HPOS
				$order->update_meta_data('tilopay_is_captured', ('yes' == $this->tpay_capture ? 1 : 0));
				$order->update_meta_data('authorization_number', $response->auth);
			} else {
				//set last action done
				update_post_meta($wc_order_id, 'tilopay_is_captured', ('yes' == $this->tpay_capture ? 1 : 0));
				update_post_meta($wc_order_id, 'authorization_number', $response->auth);
			}

			//Add update post meta to the state user select
			$order->payment_complete($response->auth);
			$order->add_order_note(__('Authorization:', 'tilopay') . $response->auth);
			$order->add_order_note(__('Code:', 'tilopay') . $response->response);
			$order->add_order_note(__('Description:', 'tilopay') . $response->description);
			// translators: %s action type.
			$order->add_order_note(sprintf(__('%s Tilopay id:', 'tilopay'), $textOrderTilopay) . $tpay_order_id);
		} else {
			$responseText = isset($response->description) ? $response->description : '';
			$responseResult = (!empty($response->result) && isset($response->result)) ? $response->result : $responseText;
			$responseResult = (!empty($response->message) && isset($response->message)) ? $response->message : $responseResult;
			$responseResult = (!empty($responseResult)) ? $responseResult : 'validation response ' . wp_json_encode($response);

			// translators: %s message get from Tilopay call api.
			$order->add_order_note(sprintf(__('Payment processing failed. Please retry, Tilopay responded with error: %s', 'tilopay'), $responseResult));

			//Update order status
			$order->set_status('wc-failed');
			$order->save();
		}

		if (is_callable(array($order, 'save'))) {
			//$order->save( );
		}

		do_action('wc_tilopay_process_response', $response, $order);

		return $response;
	}
	/**
	 * Get_icon function
	 * Return icons for card brands supported.
	 *
	 * @since 2.3.0
	 * @return string
	 */
	public function get_icon() {
		//is redirect
		if (false === $this->is_native_method) {
			//Here we are using grind system, css is located at tilopay-config-payment-front.css
			$icons_str = '';

			//first row with icons
			if (is_array($this->tpay_logo_options) && !empty($this->tpay_logo_options)) {
				$icons_str .= '<div class="Container-tilopay">
			<div class="Flex-tilopay">';
				foreach ($this->tpay_logo_options as $key => $value) {
					if (in_array($value, ['visa', 'mastercard', 'american_express', 'sinpemovil', 'credix', 'sistema_clave'])) {
						//others
						$icons_str .= '<img class="Flex-item-tilopay" src="' . TPAY_PLUGIN_URL . '/assets/images/' . $value . '.svg" style="width: 51px;	max-width: 100%!important; max-height: 100%!important; margin-right: 3px;" />';
					}
				}
				$icons_str .= '</div>
			</div>';

				//next row BAC
				if (in_array('mini_cuotas', $this->tpay_logo_options) || in_array('tasa_cero', $this->tpay_logo_options)) {
					$icons_str .= '<div class="flex-container-tpay-bac">';
					if (in_array('tasa_cero', $this->tpay_logo_options)) {
						$icons_str .= '<div>
				<img src="' . TPAY_PLUGIN_URL . '/assets/images/tasa-cero.png" style="width: 100%;max-height: none !important;" />
				</div>';
					}
					if (in_array('mini_cuotas', $this->tpay_logo_options)) {
						$icons_str .= '
					<div>
				<img src="' . TPAY_PLUGIN_URL . '/assets/images/minicuotas.png" style="width: 100%;max-height: none !important;" />
				</div>';
					}
					$icons_str .= '
			</div>';
				}
			}

			$tpay_title_div = '';
			if (null == $this->title || '' == $this->title ||  __('Pay with', 'tilopay') == $this->title) {
				# Title with logo
				$tpay_title_div .= __('Pay with', 'tilopay') . '<img class="tpay-icon-c" src="' . TPAY_PLUGIN_URL . '/assets/images/tilopay_color.png" style="display: block;"/>';
			} else {
				//Only text
				$tpay_title_div .= $this->title;
			}
?>
			<script type="text/javascript">
				(function($) {
					//remove default style
					$('label[for="payment_method_tilopay"]').remove();
					//append custom label
					var title_payment_method_tilopay = <?php echo wp_json_encode(wp_kses_post($tpay_title_div), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
					var labelElement = document.createElement('label');
					labelElement.setAttribute('class', 'payment_method_tilopay');
					labelElement.setAttribute('for', 'payment_method_tilopay');
					labelElement.innerHTML = title_payment_method_tilopay;
					$('#payment_method_tilopay').parent().append(labelElement);

					//append logos
					var icon_payment_method_tilopay = <?php echo wp_json_encode(wp_kses_post($icons_str), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
					var div_icon = document.createElement('div');
					div_icon.innerHTML = icon_payment_method_tilopay;
					$('#payment_method_tilopay').parent().append(div_icon);
				})(jQuery);
			</script>
		<?php
		} //.is redirect

		//is not redirecy
		if ($this->is_native_method) {
			//Here we are using grind system, css is located at tilopay-config-payment-front.css
			$icons_str = $this->tpay_payment_method();
			$tpay_title_div = '';
			if (null == $this->title || '' == $this->title || __('Pay with', 'tilopay') == $this->title) {
				# Title with logo
				$tpay_title_div .= '<label class="payment_method_tilopay yes-r" for="payment_method_tilopay">' .
					__('Pay with', 'tilopay') . '<img class="tpay-icon-c" src="' . TPAY_PLUGIN_URL . '/assets/images/tilopay_color.png" style="display: block;"/>';
				$tpay_title_div .= '</label>';
			}

		?>
			<script type="text/javascript">
				(function($) {
					//append logos
					var iconsStr = <?php echo wp_json_encode(wp_kses_post($icons_str), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
					var divIcon = document.createElement('div');
					divIcon.innerHTML = iconsStr;
					$('#wc-tilopay-cc-form').parent().append(divIcon);
				})(jQuery);
			</script>
		<?php
		}
		return apply_filters('woocommerce_gateway_icon', $tpay_title_div, $this->id);
	}


	public function tpay_payment_method() {
		$icons_str = '';
		//first row with icons
		if (is_array($this->tpay_logo_options) && !empty($this->tpay_logo_options)) {
			$icons_str .= '<div class="Container-tilopay">
		<div class="Flex-tilopay">';
			foreach ($this->tpay_logo_options as $key => $value) {
				if (in_array($value, ['visa', 'mastercard', 'american_express', 'sinpemovil', 'credix', 'sistema_clave'])) {
					//others
					$icons_str .= '<img class="Flex-item-tilopay" src="' . TPAY_PLUGIN_URL . '/assets/images/' . $value . '.svg" style="width: 51px;	max-width: 100%!important; max-height: 100%!important; margin-right: 3px;" />';
				}
			}
			$icons_str .= '</div>
		</div>';

			//next row BAC
			if (in_array('mini_cuotas', $this->tpay_logo_options) || in_array('tasa_cero', $this->tpay_logo_options)) {
				$icons_str .= '<div class="flex-container-tpay-bac">';
				if (in_array('tasa_cero', $this->tpay_logo_options)) {
					$icons_str .= '<div>
			<img src="' . TPAY_PLUGIN_URL . '/assets/images/tasa-cero.png" style="width: 100%;max-height: none !important;" />
			</div>';
				}
				if (in_array('mini_cuotas', $this->tpay_logo_options)) {
					$icons_str .= '
				<div>
			<img src="' . TPAY_PLUGIN_URL . '/assets/images/minicuotas.png" style="width: 100%;max-height: none !important;" />
			</div>';
				}
				$icons_str .= '
		</div>';
			}
		}
		return $icons_str;
	}

	public function tpay_retry_subscription_order($order_id) {
		$this->tpay_process_subscription($order_id, 'tpay_retry_subscription_order');
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 *
	 */
	public function init_form_fields() {
		$config_fields = \Tilopay\TilopayConfig::formConfigFields();
		$this->form_fields = apply_filters('wc_tilopay_settings', $config_fields);
	}

	/**
	 * WOO Direct Gateways
	 * Output payment fields
	 *
	 * @returnvoid
	 * Woocommerce
	 */
	public function payment_fields() {

		$current_user = wp_get_current_user();
		$current_user_email = (isset($current_user->user_email)) ? $current_user->user_email : 'init-default@tilopay.com';
		$order_id = absint(get_query_var('order-review'));

		// ok, let's display some description before the payment form
		if ($this->method_description) {
			//we need endpoint to check if cred are test or prod mode
			$this->method_description = '';
			// display the description with <p> tags etc.
			echo '<span id="environment" class=""></span>';
		}

		//$this->credit_card_form( );//default form
		if (is_ajax()  && false === $this->is_checkout_block) {

			//call SDK from tilopay-checkout.js
		?>
			<script type="text/javascript">
				initSDKTilopay();
			</script>
		<?php
		} else if (isset($_GET['pay_for_order']) && true == $_GET['pay_for_order']) {
		?>
			<script type="text/javascript">
				document.addEventListener("DOMContentLoaded", function() {
					initSDKTilopay();
				});
			</script>
<?php
		}

		// I will echo( ) the form, but you can close PHP tags and print it directly in HTML
		echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">
		<input type="hidden" id="tpay_woo_checkout_nonce" name="tpay_woo_checkout_nonce" value="' . esc_attr(wp_create_nonce($this->id . '-tpay-woo-action-nonce')) . '">
		<input type="hidden" id="tpay_env" name="tpay_env" value="PROD">
		<input type="hidden" id="token_hash_card_tilopay" name="token_hash_card_tilopay" value="">
		<input type="hidden" id="token_hash_code_tilopay" name="token_hash_code_tilopay" value="">
		<input type="hidden" id="card_type_tilopay" name="card_type_tilopay" value="">
		<input type="hidden" id="pay_sinpemovil_tilopay" name="pay_sinpemovil_tilopay" value="0">
		<input type="hidden" id="tlpy_is_yappy_payment" name="tlpy_is_yappy_payment" value="0">
		<input type="hidden" id="woo_session_tilopay" name="woo_session_tilopay" value="0">
		<div id="loaderTpay" payFormTilopay>
		 <div class="spinnerTypayInit"></div>
		</div>
		<div class="payFormTilopay" >
		<div id="overlaySubscriptions" style="display: none;">
		<p id="overlayText" style="display: none;">' . esc_html__('Subscriptions payment is not allowed in test environment.', 'tilopay') . '</p>
		</div>
		<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout" id="tpay-sdk-error-div" style="display: none;">
		<ul class="woocommerce-error" role="alert" id="tpay-sdk-error">
		</ul>
		</div>
		<div class="form-row form-row-wide">
			 <label for="tlpy_payment_method" id="methodLabel" style="display: none;">' . esc_html__('Payment methods', 'tilopay') . '</label>
			 <select name="tlpy_payment_method" id="tlpy_payment_method" class="selectwc-credit-card-form-card-select" onchange="onchange_payment_method( this );" style="display: none;">
				 <option value="" selected disabled>' . esc_html__('Select payment method', 'tilopay') . '</option>
			 </select>
		</div>
		<div class="form-row form-row-wide" id="selectCard" style="display: none;">
		<label>' . esc_html__('Saved cards', 'tilopay') . '</label>
		<select name="cards" id="cards" onchange="onchange_select_card( );" >
			 <option value="" selected disabled>' . esc_html__('Select card', 'tilopay') . '</option>
		</select>
		</div>
		<div class="form-row form-row-wide" id="yappyPhoneDiv" style="display: none;">
		<label>' . esc_html__('Yappy phone number', 'tilopay') . '</label>
		<input id="tlpy_yappy_phone" class="input-text wc-credit-card-form-phone" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="•••• ••••" name="tlpy_yappy_phone">
		</div>';

		// Add this action hook if you want your custom payment gateway to support it
		do_action('woocommerce_credit_card_form_start', $this->id);

		// I recommend to use inique IDs, because other gateways could already use #ccNo, #tlpy_cc_expiration_date, #cvc
		//<button type="button" id="pay-sinpemobil-tilopay" class="button payWithSinpeMovil" data-modal="#tilopay-m1">Pagar</button>
		echo '
	 <div id="divTpaySinpeMovil" style="display: none;">
			<p>' . esc_html__('The payment instructions with SINPE Móvil will be shown on the next screen.', 'tilopay') . ' </p><br>
		</div>
		<div id="divTpayCardForm">
		<div class="form-row form-row-wide" id="divCardNumber" style="display: none;">
		<label for="tlpy_cc_number">' . esc_html__('Card number', 'tilopay') . ' <span class="required">*</span></label>
		<input id="tlpy_cc_number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="•••• •••• •••• ••••" name="tlpy_cc_number">
		</div>
		<div class="form-row form-row-first" id="divCardDate" style="display: none;">
			<label for="tlpy_cc_expiration_date">' . esc_html__('Expiry date', 'tilopay') . ' <span class="required">*</span></label>
			<input id="tlpy_cc_expiration_date" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="MM / AA" name="tlpy_cc_expiration_date">
		</div>
		<div class="form-row form-row-last" id="divCardCvc" style="display: none;">
			<label for="tlpy_cvv">' . esc_html__('Card code ( CVC )', 'tilopay') . ' <span class="required">*</span></label>
			<input id="tlpy_cvv" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="CVV" name="tlpy_cvv" style="width:100px !important">
		</div>

		<div class="form-row" id="divSaveCard" style="display: none;">
		 <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
			<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="tpay_save_card" id="tpay_save_card">
			 <span class="woocommerce-terms-and-conditions-checkbox-text">' . esc_html__('Save card', 'tilopay') . '</span>
		 </label>
		</div>

		</div>

		<div class="clear"></div>';

		do_action('woocommerce_credit_card_form_end', $this->id);

		echo '<div class="clear"></div>
		</div>
		<div id="responseTilopay"></div>

		</fieldset>

		<!--Tilopay modal-->
		<div id="tilopay-m1" class="tilopay-modal-container">
		<div class="tilopay-overlay" data-modal="close"></div>
		<div class="tilopay-modal">
		 <h3>' . esc_html__('Pay with SINPE Móvil', 'tilopay') . '</h3>
			<p>' . esc_html__('To make the payment with SINPE Móvil, you must make sure to make the payment in the following way:', 'tilopay') . '<br>
			' . esc_html__('Telephone:', 'tilopay') . ' <strong id="tilopay-sinpemovil-number"></strong><br>
			' . esc_html__('Exact amount:', 'tilopay') . ' <strong>' . wp_json_encode(get_woocommerce_currency()) . '</strong> <strong id="tilopay-sinpemovil-amount"></strong><br>
			' . esc_html__('Specify in the description:', 'tilopay') . ' <strong id="tilopay-sinpemovil-code"></strong><br>
			</p>
		<div class="tilopay-btn-group">
		<button type="button" class="button btn-tilopay-close-modal" data-modal="close" style="margin-right: 10px;">' .
			esc_html__('Cancel', 'tilopay') .
			'</button>
		<button type="button" id="process-tilopay" class="button alt process-sinpemovil-tilopay loading" desabled>' .
			esc_html__('Waiting payment', 'tilopay') .
			'</button>
		</div>
		</div>';
	}

	public function tpay_css_front() {
		if ($this->is_native_method) {
			//css logo payment frontend
			$logo_payment_frontend = gmdate('ymd-Gis', (filemtime(TPAY_PLUGIN_DIR . '/assets/css/tilopay-config-payment-front.css') ?? time()));
			wp_register_style('tilopay-payment-front', WP_PLUGIN_URL . '/tilopay/assets/css/tilopay-config-payment-front.css', false, $logo_payment_frontend);
			wp_enqueue_style('tilopay-payment-front');
		} else {
			//css logo payment frontend
			$logo_payment_frontend = gmdate('ymd-Gis', (filemtime(TPAY_PLUGIN_DIR . '/assets/css/tilopay-redirect-payment.css') ?? time()));
			wp_register_style('tilopay-payment-redirect', WP_PLUGIN_URL . '/tilopay/assets/css/tilopay-redirect-payment.css', false, $logo_payment_frontend);
			wp_enqueue_style('tilopay-payment-redirect');
		}
	}

	//enqueue tilopay-checkout.js
	public function tpay_payment_scripts() {

		// we need JavaScript to process a token only on cart/checkout pages, right? !is_cart() &&
		if (!is_checkout() && !isset($_GET['pay_for_order'])) {
			return;
		}

		// if our payment gateway is disabled, we do not have to enqueue JS too
		if ('no' === $this->enabled) {
			return;
		}

		//SDK
		$tilopay_sdk = gmdate('ymd-Gis');
		wp_enqueue_script('tilopay-SDK', TPAY_SDK_URL, array(), $tilopay_sdk, true);

		//SDK KOUNT
		$kount_sdk = gmdate('ymd-Gis');
		$sdKountUrl = 'https://storage.googleapis.com/tilo-uploads/assets/plugins/kount/kount-web-client-sdk-bundle.js?generation=1687791530716376';
		wp_enqueue_script('tilopay-kount-SDK', $sdKountUrl, array(), $kount_sdk, true);

		$tilopay_checkout_js_file = (false === $this->is_checkout_block) ? 'tilopay-checkout.js' : 'block-tilopay-checkout.js';
		// and this is our custom JS
		$checkout_exist      = file_exists(TPAY_PLUGIN_DIR . '/tilopay/assets/js/' . $tilopay_checkout_js_file);
		$my_checkoutjs_ver = gmdate('ymd-Gis', ($checkout_exist ? filemtime($checkout_exist) : time()));
		wp_register_script('tilopay-checkout', WP_PLUGIN_URL . '/tilopay/assets/js/' . $tilopay_checkout_js_file, array('jquery'), $my_checkoutjs_ver, true);

		//SDK Tokenx
		$tokenex_sdk_v = gmdate('ymd-Gis');
		$sdTokenexUrl = 'https://api.tokenex.com/inpage/js/TokenEx-Lite.js';
		wp_enqueue_script('tilopay-tokenex-SDK', $sdTokenexUrl, array(), $tokenex_sdk_v, false);

		static $initialized = false;

		if ($initialized) {
			return;  // Si ya se ejecutó, no hace nada.
		}

		$initialized = true;
		// Init SDK data
		$getDataInit = $this->setDataInit();
		wp_localize_script('tilopay-checkout', 'tilopayConfig', $getDataInit);
		wp_enqueue_script('tilopay-checkout');
	}

	/**
	 * Validate payment fields on the frontend.
	 * __( 'Check credit or debit card details', 'tilopay' )
	 * implement nonce: https://developer.wordpress.org/reference/functions/wp_verify_nonce/
	 */
	public function validate_fields() {
		// // Clean
		if (function_exists('wc_clear_notices')) {
			wc_clear_notices();
		}

		if ($this->is_native_method  && (isset($_POST['tpay_woo_checkout_nonce']) && wp_verify_nonce(sanitize_text_field($_POST['tpay_woo_checkout_nonce']), $this->id . '-tpay-woo-action-nonce'))) {
			//check if sinpemovil SINPE ( 1: yes, 0: no )
			$payWithSinpeMovil = (isset($_POST['pay_sinpemovil_tilopay']) && '1' == $_POST['pay_sinpemovil_tilopay']) ? true : false;
			$processApplePay = sanitize_text_field(isset($_POST['process_with_apple_pay'])) ? sanitize_text_field($_POST['process_with_apple_pay']) : 0;

			$tlpy_is_yappy_payment = sanitize_text_field(isset($_POST['tlpy_is_yappy_payment'])) ? sanitize_text_field($_POST['tlpy_is_yappy_payment']) : 0;

			//Check if have suscription at cart
			$is_subscription = $this->tpay_check_have_subscription();
			$paymet_with_card = ($payWithSinpeMovil) ? false : true;

			if (($payWithSinpeMovil || $tlpy_is_yappy_payment) && $is_subscription) {
				$paymet_with_card = ($tlpy_is_yappy_payment) ? false : true;
				# mus select credicard payment
				TilopayConfig::tilopay_show_block_notices('error', __('You cannot pay subscriptions with SINPE Movíl, please pay with a credit or debit card', 'tilopay'));
				return false;
				exit;
			}

			//Subscription in test mode not allowed
			if (isset($_POST['tpay_env']) && 'PROD' !== $_POST['tpay_env'] && $is_subscription) {
				TilopayConfig::tilopay_show_block_notices('error', __('Subscriptions payment is not allowed in test environment.', 'tilopay'));
				return false;
				exit;
			}

			//if not SINPE need to validate card form
			if ($paymet_with_card && 0 == $processApplePay) {
				$token_hash_card_tilopay = (sanitize_text_field(isset($_POST['token_hash_card_tilopay'])) && !empty(sanitize_text_field($_POST['token_hash_card_tilopay']))) ? sanitize_text_field($_POST['token_hash_card_tilopay']) : '';
				$token_hash_code_tilopay = (sanitize_text_field(isset($_POST['token_hash_code_tilopay'])) && !empty(sanitize_text_field($_POST['token_hash_code_tilopay']))) ? sanitize_text_field($_POST['token_hash_code_tilopay']) : '';

				$newCard = (sanitize_text_field(isset($_POST['cards']))) ? sanitize_text_field($_POST['cards']) : 'newCard';
				$selectMethod = sanitize_text_field(isset(($_POST['tlpy_payment_method']))) ? true : false;

				if (!empty($newCard) && $selectMethod) {
					if ('newCard' == $newCard) {
						if ((empty($_POST['tlpy_cc_number']) || empty($_POST['tlpy_cc_expiration_date']) || empty($_POST['tlpy_cvv'])) &&
							(empty($_POST['token_hash_code_tilopay']) || empty($_POST['token_hash_card_tilopay']))
						) {
							TilopayConfig::tilopay_show_block_notices('error', __('Check credit or debit card details', 'tilopay'));
							return false;
							exit;
						}
						if (isset($_POST['tpay_env']) && 'PROD' === $_POST['tpay_env']) {
							//Check encript card
							if ('' == $token_hash_card_tilopay) {
								TilopayConfig::tilopay_show_block_notices('error', __('Please contact the seller because we were unable to encrypt your card details to process the payment on Tilopay or refresh the page and try again.', 'tilopay') . ' ' . __LINE__);
								return false;
								exit;
							}
							//Check encript CVV
							if ('' == $token_hash_code_tilopay) {
								TilopayConfig::tilopay_show_block_notices('error', __('Please contact the seller because we were unable to encrypt your card details to process the payment on Tilopay or refresh the page and try again.', 'tilopay') . ' ' . __LINE__);
								return false;
								exit;
							}
						}
					} else {
						if ('newCard' != $newCard && empty($_POST['tlpy_cvv'])) {
							TilopayConfig::tilopay_show_block_notices('error', __('Check credit or debit card details', 'tilopay'));
							return false;
							exit;
						}
						if (isset($_POST['tpay_env']) && 'PROD' === $_POST['tpay_env']) {
							//Check encript CVV
							if ('' == $token_hash_code_tilopay) {
								TilopayConfig::tilopay_show_block_notices('error', __('Please contact the seller because we were unable to encrypt your card details to process the payment on Tilopay or refresh the page and try again.', 'tilopay') . ' ' . __LINE__);
								return false;
								exit;
							}
						}
					}
				} else {
					TilopayConfig::tilopay_show_block_notices('error', __('Check credit or debit card details', 'tilopay'));
					return false;
					exit;
				}
			}
		}

		return true;
		exit;
	}

	/**
	 * WOO Direct Gateways
	 * Process the payment and return the result
	 *
	 * @param int $order_id
	 *
	 * @return array
	 * Woocommerce
	 */
	public function process_payment($order_id) {
		global $woocommerce;
		$order = wc_get_order($order_id);

		//Check if have nonce
		if (isset($posted_data['tpay_woo_checkout_nonce']) && false === wp_verify_nonce(sanitize_text_field($_POST['tpay_woo_checkout_nonce']), $this->id . '-tpay-woo-action-nonce')) {
			return array(
				'messages' => 'wpnonce',
				'result' => 'failure',
				'redirect' => wc_get_checkout_url() //$this->get_return_url( $order )
			);
		}

		$this->log(__METHOD__ . ':::' . __LINE__ . ', order_id:' . $order_id, 'info');
		//is redirect
		if (false === $this->is_native_method) {

			$respChek = $this->tpay_get_redirect_payment_url($order, 'redirect', __LINE__);

			$this->log(__METHOD__ . ':::' . __LINE__ . ', redirect payment order_id:' . $order_id . ', redirect response:' . print_r($respChek, true), 'info');

			return $respChek;
		} //.is redirect

		//is embedded native payment
		if ($this->is_native_method) {

			$processApplePay = sanitize_text_field(isset($_POST['process_with_apple_pay'])) ? sanitize_text_field($_POST['process_with_apple_pay']) : 0;

			$tlpy_is_yappy_payment = sanitize_text_field(isset($_POST['tlpy_is_yappy_payment'])) ? sanitize_text_field($_POST['tlpy_is_yappy_payment']) : 0;

			$this->log(__METHOD__ . ':::' . __LINE__ . ', native payment order_id:' . $order_id, 'info');

			// # Have token
			$order_total = $order->get_total();
			//validar si es false, que el check este marcado
			$subscription = $this->tpay_check_have_subscription();
			$tpay_env = (sanitize_text_field(isset($_POST['tpay_env'])) != '') ? sanitize_text_field($_POST['tpay_env']) : 'PROD';
			$tokenex_exist = ('PROD' == $tpay_env) ? 'on' : 'off';

			//if checkbox on will tokenize the card
			$tokenize_new_card = (sanitize_text_field(isset($_POST['tpay_save_card'])) &&  'PROD' == $tpay_env) ? sanitize_text_field($_POST['tpay_save_card']) : 'off';

			//check if sinpemovil
			$payWithSinpeMovil = (sanitize_text_field(isset($_POST['pay_sinpemovil_tilopay'])) && 1 == $_POST['pay_sinpemovil_tilopay']) ? true : false;

			$paymet_with_card = ($payWithSinpeMovil) ? false : true;

			if ($payWithSinpeMovil) {
				# paymen by sinpemovil is checked but payment

				if ($this->is_checkout_block) {
					// Transition the order to pending before making payment.
					//Update order status
					$order->set_status('wc-pending');
					$order->save();

					//Return to get order_id at FE only for sinpemovil
					return array(
						'result' => 'processing',
						'messages' => 'sinpemovil',
						'order_id' => $order_id,
					);
				}
				$tokenize_new_card = 'off'; // not need tokenize
				$tokenex_exist = 'off';

				if (!$this->is_checkout_block) {
					$checkout_url = wc_get_checkout_url();

					$pos = strpos($checkout_url, '?');
					$check_box_must_be_selected = (!empty($_POST['terms-field'])) ? 1 : 0;
					$get_tlpy_payment_method = (sanitize_text_field(isset($_POST['tlpy_payment_method']))) ? sanitize_text_field($_POST['tlpy_payment_method']) : 'none';
					if (false === $pos) {
						$checkout_url = $checkout_url . '?process_payment=tilopay&tlpy_payment_order=' . $order_id . '&tlpy_payment_method=' . base64_encode($get_tlpy_payment_method . '|' . $check_box_must_be_selected);
					} else {
						$checkout_url = $checkout_url . '&process_payment=tilopay&tlpy_payment_order=' . $order_id . '&tlpy_payment_method=' . base64_encode($get_tlpy_payment_method . '|' . $check_box_must_be_selected);
					}

					return array(
						'result' => 'success',
						'redirect' => $checkout_url
					);
				}
			}

			// Not need for yappy payment
			if ($tlpy_is_yappy_payment) {
				$tokenize_new_card = 'off'; // not need tokenize
				$tokenex_exist = 'off';
			}

			//cards selected
			$selectCard = sanitize_text_field(isset($_POST['cards'])) ? sanitize_text_field($_POST['cards']) : 'otra';
			//2 card is from tilopay, 1 newaone
			$tokenFromTilopay = ('newCard' == $selectCard || 'otra' == $selectCard) ? '1' : '2';
			//if token from tilopay, pass card token
			$savedTokenCard = (2 == $tokenFromTilopay) ? sanitize_text_field($_POST['cards']) : 'otra';

			$get_token_hash_card_tilopay = sanitize_text_field(isset($_POST['token_hash_card_tilopay']))
				? sanitize_text_field($_POST['token_hash_card_tilopay'])
				: '';
			$token_hash_card_tilopay = ($get_token_hash_card_tilopay && 'PROD' != $tpay_env)
				? str_replace(' ', '', $get_token_hash_card_tilopay)
				: $get_token_hash_card_tilopay;

			$tlpy_cvv_cipher = sanitize_text_field(isset($_POST['token_hash_code_tilopay'])) ? sanitize_text_field($_POST['token_hash_code_tilopay']) : '';

			//Raw
			$tlpy_cvv = sanitize_text_field(isset($_POST['tlpy_cvv'])) ? sanitize_text_field($_POST['tlpy_cvv']) : '';
			// Only prod
			if ('PROD' == $tpay_env && true === $paymet_with_card && 0 == $processApplePay) {
				//Check card cipher
				if (1 == $tokenFromTilopay && (ctype_digit(str_replace(' ', '', $get_token_hash_card_tilopay)) || '' == $get_token_hash_card_tilopay)) {
					// If card or cvv are only number mean encription error
					return $this->tpay_get_redirect_payment_url($order, 'native', __LINE__);
				}
				//Check cvv cipher
				if (ctype_digit($tlpy_cvv_cipher) || '' == $tlpy_cvv_cipher) {
					// If card or cvv are only number mean encription error
					return $this->tpay_get_redirect_payment_url($order, 'native', __LINE__);
				}
			}

			if ($paymet_with_card) {
				//check if type subscriptions and if save card not set set to tokenize on
				if (1 == $subscription) {
					$tokenize_new_card = ('otra' == $savedTokenCard && 'on' != $tokenize_new_card) ? 'on' : $tokenize_new_card;
				} else {
					$subscription = ('on' == $tokenize_new_card && 'PROD' == $tpay_env) ? 1 : 0;
				}
			}

			if ($this->is_active_HPOS) {
				//HPOS
				$order->update_meta_data('tilopay_is_captured', ('yes' == $this->tpay_capture ? 1 : 0));
			} else {
				update_post_meta($order_id, 'tilopay_is_captured', ('yes' == $this->tpay_capture ? 1 : 0));
			}

			$checkout_url = wc_get_checkout_url();

			$bodyRequestData = [
				'key' => $this->tpay_key,
				'amount' => $order_total,
				'amount_sinpe' => $order_total,
				'taxes' => '0',
				'currency' => get_woocommerce_currency(),
				'billToFirstName' => $order->get_billing_first_name(),
				'billToLastName' => $order->get_billing_last_name(),
				'billToAddress' => $order->get_billing_address_1(),
				'billToAddress2' => $order->get_billing_address_2(),
				'billToCity' => $order->get_billing_city(),
				'billToState' => $order->get_billing_state(),
				'billToZipPostCode' => $order->get_billing_postcode(),
				'billToCountry' => $order->get_billing_country(),
				'billToTelephone' => $order->get_billing_phone(),
				'billToEmail' => $order->get_billing_email(),
				'orderNumber' => $order_id,
				'capture' => 'yes' == $this->tpay_capture ? 1 : 0,
				'sessionId' => sanitize_text_field(isset($_POST['woo_session_tilopay'])) ? sanitize_text_field($_POST['woo_session_tilopay']) : 'WOO-' . time(),
				'redirect' => $this->tpay_checkout_redirect,
				'tokenex_exist' => $tokenex_exist,
				'subscription' => $subscription,
				'cvv' => $tlpy_cvv,
				'cvvEncrypted' => $tlpy_cvv_cipher,
				'card' => $token_hash_card_tilopay,
				'expDate' => sanitize_text_field(isset($_POST['tlpy_cc_expiration_date'])) ? str_replace(array('-', '_', '/', ' '), '', sanitize_text_field($_POST['tlpy_cc_expiration_date'])) : '',
				'type_card' => $tokenFromTilopay,
				'card_list' => $savedTokenCard,
				//'code' => null,
				'tokenize' => $tokenize_new_card,
				'method' => sanitize_text_field(isset($_POST['tlpy_payment_method'])) ? sanitize_text_field($_POST['tlpy_payment_method']) : '',
				//'brand' => isset( $_POST['card_type_tilopay'] ) ? $_POST['card_type_tilopay'] : '',
				'cardType' => sanitize_text_field(isset($_POST['card_type_tilopay'])) ? sanitize_text_field($_POST['card_type_tilopay']) : '',
				'platform' => 'woocommerce-nativo',
				//'codeSM' => $codeSM,
				//'referenceSinpe' => $referenceSinpe,
				'lang' => get_bloginfo('language'),
				'platform_reference' => $this->tpay_platform_detail(),
				'hashVersion' => 'V2',
				'returnData' => $this->id,
				'paymentData' => sanitize_text_field(isset($_POST['payload_apple_pay'])) ? json_decode(sanitize_text_field($_POST['payload_apple_pay']), true) : '',
				'processApplePay' => $processApplePay,
				'phoneYappy' => sanitize_text_field(isset($_POST['tlpy_yappy_phone'])) ? json_decode(sanitize_text_field($_POST['tlpy_yappy_phone']), true) : '',
			];

			//$this->log(__METHOD__ . ':::' . __LINE__ . ', request_data:' . print_r($bodyRequestData, true), 'error');

			$getPaymentResponse = $this->tpay_call_to_make_order_payment($bodyRequestData);

			$cleanPaymentResponse = $getPaymentResponse;
			if (isset($cleanPaymentResponse['data']['xml'])) {
				unset($cleanPaymentResponse['data']['xml']);
			}

			if (isset($cleanPaymentResponse['data']['card'])) {
				unset($cleanPaymentResponse['data']['card']);
			}

			$this->log(__METHOD__ . ':::' . __LINE__ . ', response data order_id:' . $order_id . ', response:' . print_r($cleanPaymentResponse, true), 'error');

			if ($getPaymentResponse && $getPaymentResponse['redirect'] && isset($getPaymentResponse['enpoint'])) {
				//redirect to endpoint
				/**
				 * Cas 1: Not 3ds, we redirect to checkout with order status approved or rejected.
				 * Case 2: Redirect html file to process 3ds challenger or get back checkout with order status approved or rejected.
				 */

				$redirect_checkout_url = $getPaymentResponse['enpoint'];
				parse_str(parse_url($redirect_checkout_url, PHP_URL_QUERY), $payment_response);

				$checkQuery = strpos($redirect_checkout_url, '?');

				if (false === $checkQuery) {
					$redirect_checkout_url = $redirect_checkout_url . '?ver=' . time();
				} else {
					$redirect_checkout_url = $redirect_checkout_url . '&ver=' . time();
				}

				/**
				 * Redirect to checkout or 3ds challenger
				 */
				return array(
					'result' => 'success',
					'redirect' => $redirect_checkout_url . '&from=' . __LINE__,
				);
			}

			return array(
				'result' => 'failure',
				'messages' => __LINE__ . ' - ' . $getPaymentResponse['message'] ?? 'Error',
				'redirect' => $checkout_url //$this->get_return_url( $order )
			);
		} //. embedded native payment

		//if nothing stop and arrive this show error
		return array(
			'messages' => 'wpnonce',
			'result' => 'failure',
			'redirect' => wc_get_checkout_url() //$this->get_return_url( $order )
		);
	}

	/**
	 * Retrieves the redirect payment URL.
	 *
	 * @param WC_Order $order The order object.
	 * @param string $call_from The source of the call. Default is 'redirect'.
	 * @param int $line The line number. Default is 0.
	 * @return array The result of the function.
	 */
	function tpay_get_redirect_payment_url($order, $call_from = 'redirect', $line = 0) {

		$headers = array(
			'Accept' => 'application/json',
			'Content-Type' => 'application/json',
			'Accept-Language' => get_bloginfo('language')
		);
		$datajson = [
			'email' => $this->tpay_user,
			'password' => $this->tpay_password
		];

		$body = wp_json_encode($datajson);

		$args = array(
			'body' => $body,
			'timeout' => '300',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => $headers,
			'cookies' => array(),
		);


		$response = wp_remote_post(TPAY_ENV_URL . 'login', $args);
		$result = json_decode($response['body']);

		$order_id = $order->get_id();
		//Check if have a token
		if (!is_wp_error($response) && isset($result->access_token)) {

			# Have token
			$order_total = $order->get_total();
			$datajson = [
				'redirect' => $this->tpay_checkout_redirect,
				'key' => $this->tpay_key,
				'amount' => $order_total,
				'currency' => get_woocommerce_currency(),
				'billToFirstName' => $order->get_billing_first_name(),
				'billToLastName' => $order->get_billing_last_name(),
				'billToAddress' => $order->get_billing_address_1(),
				'billToAddress2' => $order->get_billing_address_2(),
				'billToCity' => $order->get_billing_city(),
				'billToState' => $order->get_billing_state(),
				'billToZipPostCode' => $order->get_billing_postcode(),
				'billToCountry' => $order->get_billing_country(),
				'billToTelephone' => $order->get_billing_phone(),
				'billToEmail' => $order->get_billing_email(),
				'orderNumber' => $order_id,
				'capture' => 'yes' == $this->tpay_capture ? 1 : 0,
				'subscription' => $this->tpay_check_have_subscription(),
				'platform' => 'woocommerce-redirect',
				'lang' => get_bloginfo('language'),
				'platform_reference' => $this->tpay_platform_detail(),
				'hashVersion' => 'V2',
				'returnData' => $this->id
			];
			if ($this->have_subscription && ((function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order_id)) || (function_exists('wcs_is_subscription') && wcs_is_subscription($order_id)))) {
				$datajson['subscription'] = 1;
			}

			$this->log(__METHOD__ . ':::' . __LINE__ . ', redirect payment order_id:' . $order_id . ', request:' . print_r($datajson, true), 'info');

			$headers = array(
				'Authorization' => 'bearer ' . $result->access_token,
				'Content-type' => 'application/json',
				'Accept' => 'application/json',
				'Accept-Language' => get_bloginfo('language')
			);

			$body = wp_json_encode($datajson);

			$args = array(
				'body' => $body,
				'timeout' => '300',
				'redirection' => '5',
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => $headers,
				'cookies' => array(),
			);
			$response = wp_remote_post(TPAY_ENV_URL . 'processPayment', $args);
			$result = json_decode($response['body']);

			$this->log(__METHOD__ . ':::' . __LINE__ . ', redirect payment order_id:' . $order_id . ', response:' . print_r($result, true), 'info');

			if (100 == $result->type) {
				$tpay_url_payment_form = $result->url;

				if ('native' === $call_from) {
					$order->add_order_note('Error: ' . __('Card encryption failed, trying to redirect to the form to make the payment.', 'tilopay'));
				}

				if ($this->is_active_HPOS) {
					//HPOS
					$order->update_meta_data('tilopay_html_form', $tpay_url_payment_form);
					$order->update_meta_data('tilopay_is_captured', ('yes' == $this->tpay_capture ? 1 : 0));
					//If nativo call just redirect to Tilopay form
					if ('native' === $call_from) {
						$order->update_meta_data('tpay_was_redirect_native', 'yes');
					}

					$order->save();
				} else {
					update_post_meta($order_id, 'tilopay_html_form', $tpay_url_payment_form);
					update_post_meta($order_id, 'tilopay_is_captured', ('yes' == $this->tpay_capture ? 1 : 0));
					//If nativo call just redirect to Tilopay form
					if ('native' === $call_from) {
						update_post_meta($order_id, 'tpay_was_redirect_native', 'yes');
					}
				}

				$checkout_url = wc_get_checkout_url();
				//If nativo call just redirect to Tilopay form
				$for_native_error = '';
				if ('native' === $call_from) {
					$for_native_error = '&cipherError=1';
				};

				$pos = strpos($checkout_url, '?');

				if (false === $pos) {
					$checkout_url = $checkout_url . '?process_payment=tilopay&tlpy_payment_order=' . $order_id . $for_native_error;
				} else {
					$checkout_url = $checkout_url . '&process_payment=tilopay&tlpy_payment_order=' . $order_id . $for_native_error;
				}

				return array(
					'result' => 'success',
					'redirect' => $checkout_url
				);
			} else if (in_array($result->type, [400, 401, 402, 403, 404])) {
				//Key not found
				// TilopayConfig::tilopay_show_block_notices('error', __("There seems to be an issue with this website's integration with Tilopay, please inform the seller to complete your purchase.", 'tilopay') . ' Process Payment ' . __LINE__);
			} else if (300 == $result->type) {
				//TilopayConfig::tilopay_show_block_notices('error', __('You have license errors, please try again.', 'tilopay'), 'error');
			} else {
				//Defult message
				// TilopayConfig::tilopay_show_block_notices('error', __("There seems to be an issue with this website's integration with Tilopay, please inform the seller to complete your purchase.", 'tilopay') . ' Process Payment ' . __LINE__, 'error');
			}
		} else {
			//Dont have token
			$responseBodyError = isset($response['body']) ? $response['body'] : '';
			$this->log(__METHOD__ . ':::' . __LINE__ . ', order_id:' . $order_id . ', response:', print_r($responseBodyError, true), 'error');
			// TilopayConfig::tilopay_show_block_notices('error', __("There seems to be an issue with this website's integration with Tilopay, please inform the seller to complete your purchase.", 'tilopay') . ' Process Payment ' . __LINE__, 'error');
		} //.End check have token

		return array(
			'result' => 'failure',
			'messages' => __("There seems to be an issue with this website's integration with Tilopay, please inform the seller to complete your purchase.", 'tilopay')
		);
	}

	/**
	 *
	 * Process payment modifications
	 *
	 */
	public function tpay_process_payment_modification($order_id, $type, $order_total) {
		/**
		 * $type:
		 * 1 = Capture ( captura )
		 * 2 = Refund ( reembolso )
		 * 3 = Reversal ( reverso )
		 */
		$order = wc_get_order($order_id);
		$headers = array(
			'Accept' => 'application/json',
			'Content-Type' => 'application/json',
			'Accept-Language' => get_bloginfo('language')
		);
		$datajson = [
			'email' => $this->tpay_user,
			'password' => $this->tpay_password
		];

		$body = wp_json_encode($datajson);

		$args = array(
			'body' => $body,
			'timeout' => '300',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => $headers,
			'cookies' => array(),
		);


		$getCallTpayAPI = wp_remote_post(TPAY_ENV_URL . 'login', $args);
		if (is_wp_error($getCallTpayAPI)) {
			$responseBodyError = isset($getCallTpayAPI['body']) ? $getCallTpayAPI['body'] : '';
			$this->log(__METHOD__ . ':::' . __LINE__ . ', order_id:' . $order_id . ', response:', print_r($responseBodyError, true), 'error');
			return false;
		}

		//All is ok
		$getTpayResponseDecode = json_decode($getCallTpayAPI['body']);

		//Check if have a token
		if (isset($getTpayResponseDecode->access_token)) {
			# Have token
			$headers = array(
				'Authorization' => 'bearer ' . $getTpayResponseDecode->access_token,
				'Content-type' => 'application/json',
				'Accept' => 'application/json',
				'Accept-Language' => get_bloginfo('language')
			);

			$dataJsonModify = [
				'orderNumber' => $order_id,
				'key' => $this->tpay_key,
				'amount' => $order_total,
				'type' => $type,
				'hashVersion' => 'V2',
				'platform' => ($this->is_native_method) ? 'woocommerce-nativo' : 'woocommerce-redirect',
				'platform_reference' => $this->tpay_platform_detail(),
			];
			$bodyModify = wp_json_encode($dataJsonModify);

			$args = array(
				'body' => $bodyModify,
				'timeout' => '300',
				'redirection' => '5',
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => $headers,
				'cookies' => array(),
			);
			$getCallTpayAPIModification = wp_remote_post(TPAY_ENV_URL . 'processModification', $args);
			if (is_wp_error($getCallTpayAPIModification)) {
				$responseBodyError = isset($getCallTpayAPIModification['body']) ? $getCallTpayAPIModification['body'] : '';
				$this->log(__METHOD__ . ':::' . __LINE__ . ', request_data:' . print_r($responseBodyError, true), 'error');
				return false;
			}

			$getModificationResDecode = json_decode($getCallTpayAPIModification['body']);

			return $getModificationResDecode;
		} //.End check have token

		//Error default
		return false;
	}


	/**
	 * All payment icons that work with TILOPAY. Some icons references
	 * WC core icons.
	 *
	 * @since 2.3.0
	 * @return array
	 */
	public function payment_icons() {
		return apply_filters(
			'tilopay',
			array(
				'visa' => '<img src="' . TPAY_PLUGIN_URL . '/assets/images/visa.svg" style="float: none; max-height: 20px; margin:0px 10px" alt="Visa" />',
				'amex' => '<img src="' . TPAY_PLUGIN_URL . '/assets/images/amex.svg" style="float: none; max-height: 20px; margin:0px 10px"alt="Ame" />',
				'mastercard' => '<img src="' . TPAY_PLUGIN_URL . '/assets/images/mastercard.svg" style="float: none; max-height: 20px; margin:0px 10px" alt="Mastercard" />',
			)
		);
	}

	/**
	 * Check incoming requests for Tilopay Webhook data and process them.
	 */
	public function tpay_payment_webhook_process() {
		if (
			isset($_SERVER['REQUEST_METHOD']) && ('POST' !== $_SERVER['REQUEST_METHOD'])
			|| !isset($_GET['wc-api'])
		) {
			return;
		}

		//is post method and wc-api;
		$request_body		= file_get_contents('php://input');
		$responseJson		= (object) json_decode($request_body);
		if (JSON_ERROR_NONE !== json_last_error()) {
			return wp_send_json(array(
				'code' => 404,
				'message' => 'Unknown request body json',
				'data' => json_decode($request_body)
			), 404);
			exit;
		}

		//json decode successefully
		if ((!empty($responseJson->orderNumber) && isset($responseJson->orderNumber)) &&
			(!empty($responseJson->code) && isset($responseJson->code)) &&
			(!empty($responseJson->orderHash) && isset($responseJson->orderHash)) &&
			(!empty($responseJson->tpt) && isset($responseJson->tpt))
		) {

			$orderNumber = $responseJson->orderNumber;
			$code = $responseJson->code;
			$tpay_order_id = $responseJson->tpt;
			$auth = $responseJson->auth;
			//Get Woocommerce order
			global $woocommerce;

			$order = wc_get_order($orderNumber);
			$wc_order_id = $order->get_id();

			$orderHash = $responseJson->orderHash;
			if (!empty($orderHash) && isset($orderHash) && 64 == strlen($orderHash)) {

				$amount = $order->get_total();

				// Get the Customer billing email
				$billing_email = $order->get_billing_email();

				// Get the customer or user id from the order object
				$customer_id = $order->get_customer_id();

				//computed hash_hmac
				$customerOrderHash = $this->computed_customer_hash($orderNumber, $amount, get_woocommerce_currency(), $tpay_order_id, $code, $auth, $billing_email);

				//Use hmac data to check if the response is from Tilopay or not
				if (!hash_equals($customerOrderHash, $orderHash)) {

					$order->add_order_note(__('Description:', 'tilopay') . __('Order with failed payment', 'tilopay'));
					// translators: %s the order number.
					$get_traslate_message = sprintf(__('Invalid order confirmation, please check and try again or contact the seller to completed you order no.%s', 'tilopay'), $orderNumber) . ', order has status: ' . $order->get_status();
					//response api
					return wp_send_json(array(
						'code' => 400,
						'message' => $get_traslate_message,
						'data' => json_decode($request_body)
					), 400);
					exit;
				} else if (hash_equals($customerOrderHash, $orderHash) && !empty($code)) {
					/**
					 * The hmac hash is equal from Tiloay server,
					 * Process to check if the payment was approved to pudate the order status payment.
					 */

					if (1 == $code && !empty($responseJson->auth) && isset($responseJson->auth)) {
						$auth = $responseJson->auth;
						/**
						 * If payment was approve by Tilopay
						 * Process to pudate the order status payment.
						 */

						if ($this->is_active_HPOS) {
							// HPOS
							$existing_auth_code = $order->get_meta('tilopay_auth_code', true);
						} else {
							$existing_auth_code = get_post_meta($auth, 'tilopay_auth_code', true);
						}
						//check if order is pending || on-hold to update status
						if (in_array($order->get_status(), ['on-hold']) && !empty($existing_auth_code) && 'Pending' == $existing_auth_code) {

							//Update order status
							$order->set_status('wc-processing');
							if ($this->is_active_HPOS) {
								//HPOS
								$order->update_meta_data('tilopay_auth_code', $auth);
							} else {
								update_post_meta($orderNumber, 'tilopay_auth_code', $auth);
							}
							// translators: %s action type.
							$order->add_order_note(sprintf(__('%s Tilopay id:', 'tilopay'), 'PayCash') . $tpay_order_id);
							$order->add_order_note(__('Authorization:', 'tilopay') . $auth);
							$order->add_order_note(__('Code:', 'tilopay') . $code);
							$order->add_order_note(__('Description:', 'tilopay') . __('Payment was successfully', 'tilopay'));
							$order->save();
						}
						//response api
						return wp_send_json(array(
							'code' => 200,
							'message' => 'Great order update to ' . $order->get_status(),
							'data' => json_decode($request_body)
						), 200);
						exit;
					} else if ('Pending' == $code) {
						if (empty($existing_auth_code)) {
							//Update order status to pending
							$order->add_order_note(__('Code:', 'tilopay') . $code);

							//Update order status
							$order->set_status('wc-on-hold');
						}
						$order->add_order_note(__('Description:', 'tilopay') . __('Payment is pending.', 'tilopay')); //si lo devuelve
						$order->save();

						//Response api
						return wp_send_json(array(
							'code' => 200,
							'message' => 'Order has status: ' . $order->get_status(),
							'data' => json_decode($request_body)
						), 200);
						exit;
					} else {
						//The payment was not approved by Tilopay
						$order->add_order_note(__('Order with failed payment', 'tilopay'));
						$order->add_order_note(__('Code:', 'tilopay') . $code);

						//Update order status
						$order->set_status('wc-failed');
						$order->save();

						//Response api
						return wp_send_json(array(
							'code' => 200,
							'message' => __('Order with failed payment', 'tilopay') . ', order has status: ' . $order->get_status(),
							'data' => json_decode($request_body)
						), 200);
						exit;
					}
				} else {
					//No hash or not equals
					$order->add_order_note(__('Description:', 'tilopay') . __('Order with failed payment', 'tilopay'));

					//Response api
					return wp_send_json(array(
						'code' => 400,
						'message' => __('Order with failed payment', 'tilopay') . ', order has status: ' . $order->get_status(),
						'data' => json_decode($request_body)
					), 400);
					exit;
				}
			}
		}

		return wp_send_json(array(
			'code' => 500,
			'message' => 'Unknown error',
			'data' => json_decode($request_body)
		), 500);
		exit;
	}

	public function tpay_get_token_sdk() {
		if (isset($this->tpay_user) && isset($this->tpay_password)) {
			$headers = array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'Accept-Language' => get_bloginfo('language')
			);
			$datajson = [
				'apiuser' => $this->tpay_user,
				'password' => $this->tpay_password,
				'key' => $this->tpay_key
			];

			$body = wp_json_encode($datajson);

			$args = array(
				'body' => $body,
				'timeout' => '300',
				'redirection' => '5',
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => $headers,
				'cookies' => array(),
			);


			$response = wp_remote_post(TPAY_ENV_URL . 'loginSdk', $args);
			if (is_wp_error($response)) {
				$responseBodyError = $response->get_error_message();
				$this->log(__METHOD__ . ':::' . __LINE__ . ', response error message: ' . $responseBodyError, 'error');
				return false;
			}
			$result = json_decode($response['body']);

			if (isset($result->error)) {
				$this->log(__METHOD__ . ':::' . __LINE__ . ', response:' . print_r($response['body'], true), 'error');
				return false;
			}

			//"expires_in": 86400
			//Check if have a token
			if (isset($result->access_token)) {
				return $result->access_token;
			}
		}
		return false;
	}


	/**
	 * Helper to set SDK config
	 *
	 * @return array
	 */
	public function setDataInit() {
		/**
		 * To get global
		 * Global $woocommerce; ( $woocommerce->cart->total ) ? $woocommerce->cart->total : 99999
		 */

		$time = time();
		$orderNumber = 'init-default-' . $time;
		$is_user_logged_in = 0;
		$email_current_user = 'john-doe-' . $time . '@tilopay.com';
		$firstname_current_user = 'John';
		$lastname_current_user = 'Doe';
		//from WOO customer
		$billing_phone_current_user = '88888888';
		$billing_address_1_current_user = 'San Jose';
		$billing_address_2_current_user = 'Aserri';
		$billing_city_current_user = 'SJO';
		$billing_state_current_user = 'SJO';
		$billing_postcode_current_user = '1001';
		$billing_country_current_user = 'CR';
		if (is_user_logged_in()) {
			$is_user_logged_in = 1;
			$current_user = wp_get_current_user();
			$email_current_user = ($current_user) ? $current_user->user_email : $email_current_user;
			// Get an instance of the WC_Customer Object from the user ID
			$customer = new WC_Customer($current_user->ID);
			//from WOO customer
			$firstname_current_user = ($customer->get_billing_first_name() != null) ? $customer->get_billing_first_name() : $firstname_current_user;
			$lastname_current_user = ($customer->get_billing_last_name() != null) ? $customer->get_billing_last_name() : $lastname_current_user;
			$billing_phone_current_user = ($customer->get_billing_phone() != null) ? $customer->get_billing_phone() : $billing_phone_current_user;
			$billing_address_1_current_user = ($customer->get_billing_address_1() != null) ? $customer->get_billing_address_1() : $billing_address_1_current_user;
			$billing_address_2_current_user = ($customer->get_billing_address_2() != null) ? $customer->get_billing_address_2() : $billing_address_2_current_user;
			$billing_city_current_user = ($customer->get_billing_city() != null) ? $customer->get_billing_city() : $billing_city_current_user;
			$billing_state_current_user = ($customer->get_billing_state() != null) ? $customer->get_billing_state() : $billing_state_current_user;
			$billing_postcode_current_user = ($customer->get_billing_postcode() != null) ? $customer->get_billing_postcode() : $billing_postcode_current_user;
			$billing_country_current_user = ($customer->get_billing_country() != null) ? $customer->get_billing_country() : $billing_country_current_user;
		}
		$envMode = __('TEST MODE ENABLED. In test mode, you can use the card numbers listed in', 'tilopay');
		$integrationError = __("There seems to be an issue with this website's integration with Tilopay, please inform the seller to complete your purchase.", 'tilopay') . ' Set Data Init ' . __LINE__;
		//check total with tax incluide
		$cart_total_price = 0;
		$wooSessionTpay = 'WOO-' . time();
		if (WC()->cart) {
			$cart_total_price = WC()->cart->get_total('raw');
			$wooSessionTpay = (WC()->cart->get_cart_hash()) ? 'WOO-' . WC()->cart->get_cart_hash() : $wooSessionTpay;
		}
		$haveSubscription = $this->tpay_check_have_subscription();
		if (isset($_GET['pay_for_order']) && true == $_GET['pay_for_order']) {
			$order = wc_get_order(get_query_var('order-pay'));
			$orderNumber = $order->get_order_number();
			$cart_total_price = $order->get_total();
			$wooSessionTpay = ($order->get_order_key()) ? $order->get_order_key() : 'WOO-' . time();
			$haveSubscription = (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order));
		}
		//should 32 characters
		$wooSessionTpay = (strlen($wooSessionTpay) <= 25) ? $wooSessionTpay : substr($wooSessionTpay, 0, (25 - strlen($wooSessionTpay)));

		return array(
			'token' => $this->tpay_get_token_sdk(),
			'currency' => get_woocommerce_currency(),
			'language' => get_bloginfo('language'),
			'amount' => $cart_total_price,
			'amount_sinpe' => $cart_total_price,
			'billToFirstName' => $firstname_current_user,
			'billToLastName' => $lastname_current_user,
			'billToAddress' => $billing_address_1_current_user,
			'billToAddress2' => $billing_address_2_current_user,
			'billToCity' => $billing_city_current_user,
			'billToState' => $billing_state_current_user,
			'billToZipPostCode' => $billing_postcode_current_user,
			'billToCountry' => $billing_country_current_user,
			'billToTelephone' => $billing_phone_current_user,
			'billToEmail' => $email_current_user,
			'orderNumber' => $orderNumber,
			'capture' => 'yes' == $this->tpay_capture ? 1 : 0,
			'redirect' => $this->tpay_checkout_redirect,
			'subscription' => $this->tpay_check_have_subscription(),
			'platform' => 'woocommerce',
			'platform_reference' => $this->tpay_platform_detail(),
			'envMode' => $envMode,
			'integrationError' => $integrationError,
			'newCardText' => __('Pay with another card', 'tilopay'),
			'userDataIn' => $is_user_logged_in,
			'cardError' => __('Check credit or debit card details', 'tilopay'),
			'urlTilopay' => TPAY_BASE_URL,
			'Key' => $this->tpay_key,
			'tpayPluginUrl' => TPAY_PLUGIN_URL,
			'hashVersion' => 'V2',
			'haveSubscription' => $haveSubscription,
			'wooSessionTpay' => $wooSessionTpay,
			'returnData' => $this->id
		);
	}

	/**
	 * Make platformDetail
	 *
	 * @return json object
	 */
	public function tpay_platform_detail() {
		$wooVersion = (null != WC_VERSION) ? WC_VERSION : null;
		// Get the WP Version global.
		global $wp_version;
		$wpVersion = ($wp_version) ? $wp_version : null;
		$user_agent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));

		return json_encode([
			'pluginTilopay' => 'V->' . TPAY_PLUGIN_VERSION,
			'woocommerce' => 'V->' . $wooVersion,
			'WordPress' => 'V->' . $wpVersion,
			'HPOS' => true,
			'userAgentWP' => $user_agent,
			'wooBlocks' => true
		]);
	}

	public function tpay_call_to_make_order_payment($bodyRequest) {
		$headers = array(
			'Accept' => 'application/json',
			'Content-Type' => 'application/json',
			'Accept-Language' => get_bloginfo('language')
		);

		$body = wp_json_encode($bodyRequest);

		$args = array(
			'body' => $body,
			'timeout' => '300',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => $headers,
			'cookies' => array(),
			'method' => 'POST',
		);


		$response = wp_remote_post(TPAY_BASE_URL . 'admin/processPaymentFAC', $args);

		$cleanBodyRequest = $bodyRequest;
		// clean logs;
		$cleanBodyRequest['paymentData'] = '...';
		$cleanBodyRequest['expDate'] = '...';
		$cleanRequest['cvvEncrypted'] = '...';
		$cleanBodyRequest['cvv'] = '...';
		$cleanBodyRequest['card'] = '...';

		if (is_wp_error($response)) {
			//erros
			$responseBodyError = isset($response['body']) ? $response['body'] : '';
			$this->log(__METHOD__ . ':::' . __LINE__ . ' response:' . print_r($responseBodyError, true) . ', request_data:' . print_r($cleanBodyRequest, true), 'error');
			TilopayConfig::tilopay_show_block_notices('error', 'Error: ' . __("There seems to be an issue with this website's integration with Tilopay, please inform the seller to complete your purchase.", 'tilopay') . ' Make Order Payment ' . __LINE__, 'error');
			return false;
		}

		$getBody = json_decode($response['body'], true);

		if ($getBody) {
			switch ($getBody['type']) {
				case '400':
					# Tokenex

					$callTokenex = false;
					if ('TOKENEX' == $getBody['card']['brand']) {
						# callback Tilopay
						$callTokenex = $this->tpay_callback_tilopay_to_process_tokenex($getBody);
					}

					return [
						'status' => ($callTokenex) ? '200' : '400',
						'type' => $getBody['type'],
						'message' => '',
						'enpoint' => ($response) ? $callTokenex : wc_get_checkout_url(),
						'redirect' => ($callTokenex) ? true : false,
						'data' => $getBody,
					];
					break;
				case '100':
					# 3ds
					$getHtml = $getBody['htmlFormData'];
					$temp_3ds_url_file = '';
					//if 3ds html not empty
					if ('' != $getHtml) {
						//make temp 3ds file
						$temp_3ds_url_file = $this->tpay_make_temp_3ds_file($getHtml);
					}

					return [
						'status' => ($temp_3ds_url_file) ? '200' : '400',
						'type' => $getBody['type'],
						'message' => '',
						'enpoint' => $temp_3ds_url_file,
						'redirect' => true,
						'data' => $getBody,
					];
					break;
				case '200':
					# reload or approved
					return [
						'status' => ($getBody['url']) ? '200' : '400',
						'type' => $getBody['type'],
						'message' => '',
						'enpoint' => $getBody['url'],
						'redirect' => true,
						'data' => $getBody,
					];
					break;
				default:
					# error
					$this->log(__METHOD__ . ':::' . __LINE__ . 'request_payload:' . print_r($cleanBodyRequest, true), 'info');
					$this->log(__METHOD__ . ':::' . __LINE__ . 'response_body:' . print_r($getBody, true), 'info');

					$getErrorResponse = sanitize_text_field(isset($getBody['result'])) ? sanitize_text_field($getBody['result']) : '';
					$getErrorResponse = isset($getBody['message']) ? sanitize_text_field($getBody['message']) : $getErrorResponse;
					$error_message_validation = 'Your payment could not be processed, please check and try again';

					return [
						'status' => '400',
						'type' => $getBody['type'],
						'message' => 'Error: ' . __($error_message_validation, 'tilopay') . ': ' . $getErrorResponse,
						'enpoint' => wc_get_checkout_url(),
						'redirect' => false,
						'data' => $getErrorResponse,
					];
					break;
			}
		}
		//default
		return false;
	}


	/**
	 * Helper to call tokenex and process payment 3ds or just redirect
	 *
	 * @param array $bodyRequest
	 * @throws None
	 * @return mixed boolean || string || array || json
	 */
	public function tpay_callback_tilopay_to_process_tokenex($bodyRequest) {


		$headers = array(
			'Accept' => 'application/json',
			'Content-Type' => 'application/json',
			'Accept-Language' => get_bloginfo('language')
		);

		$body = wp_json_encode(['data' => $bodyRequest]);

		$args = array(
			'body' => $body,
			'timeout' => '300',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => $headers,
			'cookies' => array(),
			'method' => 'POST',
		);


		$response = wp_remote_post(TPAY_BASE_URL . 'admin/processPaymentTokenex', $args);

		if (is_wp_error($response)) {
			//erros
			$responseBodyError = isset($response['body']) ? $response['body'] : '';
			$this->log(__METHOD__ . ':::' . __LINE__ . ', response:' . print_r($responseBodyError, true), 'error');
			TilopayConfig::tilopay_show_block_notices('error', 'Error: ' . __("There seems to be an issue with this website's integration with Tilopay, please inform the seller to complete your purchase.", 'tilopay') . ' Process Tokenex ' . __LINE__, 'error');
			return false;
		}
		$getBody = json_decode($response['body'], true);

		if ($getBody) {
			if ('400' == $getBody['type'] && $this->retryCount < $this->maxRetries) {
				$this->retryCount++;
				//recursive call
				$callTokenex = $this->tpay_callback_tilopay_to_process_tokenex($getBody);
			} else if ('100' == $getBody['type']) {
				# get html
				$getHtml = isset($getBody['htmlFormData']) ? $getBody['htmlFormData'] : '';
				$temp_3ds_url_file = false;
				//if 3ds html not empty
				if ('' != $getHtml) {
					//insert script to auto clik #tilopay_place
					$insertScript = '<script>$( "#tilopay_place", window.parent.document ).trigger( "click" );</script></body>';
					$getHtml = str_replace('</body>', $insertScript, $getHtml);
					//make temp 3ds file
					$temp_3ds_url_file = $this->tpay_make_temp_3ds_file($getHtml);
				}
				//string url to redirect from temp file
				return $temp_3ds_url_file;
			} else if ('200' == $getBody['type']) {
				//string url to redirect
				return $getBody['url'];
			} else {
				//string error
				return $getBody['result'];
			}
		}
		return false;
	}

	public function tpay_make_temp_3ds_file($getHtml) {
		//insert script to show spinner Tilopay
		$insertScript = '<body><style>#loading{position: absolute;left: 50%;top: 50%;z-index: 1;width: 150px;height: 150px;margin: -75px 0 0 -75px;border: 16px solid #f3f3f3;border-radius: 50%;border-top: 16px solid #ff3644 ;width: 120px;height: 120px;animation: spin 2s linear infinite;}@keyframes spin {0% { transform: rotate( 0deg ); }100% { transform: rotate( 360deg ); }}</style><div class="d-flex justify-content-center"><div class="spinner-border" role="status" ><span class="sr-only" id="loading"></span></div></div>';
		$getHtml = str_replace('<body>', $insertScript, $getHtml);

		$fileName = '3ds_payment.html';
		$folder = WP_PLUGIN_DIR . '/tilopay/includes/' . $fileName;
		//remove file each time have been used
		if (file_exists($folder)) {
			unlink($folder);
		}
		$file_3ds_temp = fopen($folder, 'a');
		fputs($file_3ds_temp, $getHtml);
		fclose($file_3ds_temp);
		//retur url
		return plugins_url($fileName, __FILE__);
	}

	/**
	 *
	 * Update payment status
	 * $this->tpay_capture_yes = Have the user order status config from admin
	 * Payment status: ( 'yes' == $this->tpay_capture ) ? set user status config : pending
	 *
	 */
	public function tpay_get_order_status() {
		if ('yes' == $this->tpay_capture) {
			return ($this->tpay_capture_yes) ? $this->tpay_capture_yes : 'processing';
		}
		//Default
		return 'pending';
	}

	/**
	 * Check product have suscriptions
	 */
	public function tpay_check_have_subscription() {
		$is_subscription = 0;
		//Check if have suscription at cart
		if (class_exists('WooCommerce') && !empty(WC()->cart)) {
			$cart_items = WC()->cart->get_cart();

			foreach ($cart_items as $cart_item_key => $cart_item) {
				$product_id = $cart_item['product_id'];
				$product = wc_get_product($product_id);

				if ($product && in_array($product->get_type(), ['subscription', 'variable-subscription'])) {
					$is_subscription = 1;
					break;
				}
			}
		} else {
			$order = wc_get_order();
			if ($order) {
				$order_id = $order->get_id();
				$is_subscription = ((function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order_id)) || (function_exists('wcs_is_subscription') && wcs_is_subscription($order_id)));
			}
		}

		return $is_subscription;
	}

	/**
	 * Check if using redirect or native.
	 */
	public function isNativePayment() {
		return $this->is_native_method;
	}

	/**
	 * Retrieves the Tilopay Getaway settings.
	 *
	 * @return mixed The Tilopay Getaway settings.
	 */
	function tilopay_getaway_settings() {
		return $this->tilopay_getaway_settings;
	}
}
