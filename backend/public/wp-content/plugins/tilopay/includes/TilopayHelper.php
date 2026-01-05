<?php

/**
 * Helper code call to organize the class code
 *
 * @package  Tilopay
 */

namespace Tilopay;

use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Email;
use WC_Order;

class TilopayHelper {
	public $page_id;

	function tilopay_allowed_redirect_hosts($allowed_hosts) {
		$allowed_hosts[] = 'tilopay.com';
		$allowed_hosts[] = 'app.tilopay.com';
		$allowed_hosts[] = 'staging--incredible-melomakarona-d1c124.netlify.app';
		$allowed_hosts[] = 'tilopay-staging-kb4ui2z5cq-uc.a.run.app';
		$allowed_hosts[] = 'securepayment.tilopay.com';
		$allowed_hosts[] = 'secure.tilopay.com';
		$allowed_hosts[] = 'staging.tilopay.com';

		return $allowed_hosts;
	}

	function tilopay_mark_order_email_sent($return = false, $email_id = '', $email = '') {
		// Check if the email is related to an order and the payment method is 'tilopay'
		if ($email instanceof WC_Email && isset($email->object) && $email->object instanceof WC_Order) {
			$order = $email->object;

			if ($order->get_payment_method() === 'tilopay') {
				(new WCTilopay())->log(__METHOD__ . ':::' . __LINE__ . ', email sent, order_id:' . $order->get_id() . ', email_id:' . $email_id, 'info');
			}
		}
	}


	function handle_query_params_tilopay_return() {

		$tilopay_getaway_instance = new WCTilopay();

		// $notice_type = 'error';
		$message = 'Displays block notices based on the notice type and provided notices.';

		//TilopayConfig::tilopay_show_block_notices('error', $message);
		//TilopayConfig::tilopay_show_block_notices('success', $message);

		if (sanitize_text_field(isset($_GET['message_error']))) {
			$tilopay_getaway_instance->log(__METHOD__ . ':::' . __LINE__ . ', message_error query params:' . print_r($_GET, true), 'info');
			TilopayConfig::tilopay_show_block_notices('error', 'Error: ' . __(sanitize_text_field($_GET['message_error']), 'tilopay'));
			return;
		}

		$native_redirect = (isset($_GET['cipherError']))
			? sanitize_text_field($_GET['cipherError'])
			: 0;

		/**
		 * Check if from ajax and have process_payment and order_id
		 */
		if (false === $tilopay_getaway_instance->isNativePayment()) {
			$tilopay_getaway_instance->tpay_apply_redirect_to_payment_form(false);
			//return;
		} else if (1 == $native_redirect) {
			$tilopay_getaway_instance->tpay_apply_redirect_to_payment_form(true);
			//return;
		}

		$this->tilopay_check_order_payment_from_query_params();
	}

	function tilopay_check_order_payment_from_query_params() {

		$tilopay_getaway_instance = new WCTilopay();

		//Get computed hash from Tilopay Server and compareted, the hash string have 64 characters
		$request_tpay_order_id = (sanitize_text_field(isset($_GET['tpt']))) ? sanitize_text_field($_GET['tpt']) : '';
		$computed_hash_hmac_tilopay_server = (sanitize_text_field(isset($_GET['OrderHash']))) ? sanitize_text_field($_GET['OrderHash']) : '';
		$request_order_id = (sanitize_text_field(isset($_GET['order']))) ? sanitize_text_field(wp_unslash($_GET['order'])) : '';
		$request_code_payment = sanitize_text_field(isset($_GET['code'])) ? sanitize_text_field($_GET['code']) : '';
		$request_auth_code_payment = sanitize_text_field(isset($_GET['auth'])) ? sanitize_text_field($_GET['auth']) : '';
		$request_description = (sanitize_text_field(isset($_GET['description']))) ? sanitize_text_field($_GET['description']) : __('Unknown', 'tilopay');
		$request_token_card = (sanitize_text_field(isset($_GET['crd']))) ? sanitize_text_field($_GET['crd']) : '';
		$selected_method = (sanitize_text_field(isset($_GET['selected_method']))) ? sanitize_text_field($_GET['selected_method']) : '';

		if (!empty($request_order_id)) {

			$tilopay_getaway_instance->log(__METHOD__ . ':::' . __LINE__ . ', query params:' . print_r($_GET, true), 'info');

			//remove file each time have been used
			if (file_exists(WP_PLUGIN_DIR . '/tilopay/includes/3ds_payment.html')) {
				unlink(WP_PLUGIN_DIR . '/tilopay/includes/3ds_payment.html');
			}

			global $woocommerce;
			$order = wc_get_order($request_order_id); //working with notice php

			if ($order) {
				$check_unpaid_status = (!in_array($order->get_status(), ['wc-processing', 'processing', 'refunded', 'wc-refunded', 'completed', 'wc-completed', 'cancelled', 'wc-cancelled']));

				$tilopay_getaway_settings = $tilopay_getaway_instance->tilopay_getaway_settings();
				$capture_payment = $tilopay_getaway_settings['capture'];

				$wc_order_id = $order->get_id();

				// Get the Customer billing email
				$billing_email = $order->get_billing_email();

				if (TilopayConfig::tilopay_check_is_active_HPOS()) {
					// HPOS
					$existing_auth_code = $order->get_meta('tilopay_auth_code', true);
				} else {
					$existing_auth_code = get_post_meta($request_auth_code_payment, 'tilopay_auth_code', true);
				}

				$textOrderTilopay = ('yes' == $capture_payment) ? __('Capture', 'tilopay') : __('Authorization', 'tilopay');
				$orderType = ('yes' == $capture_payment) ? 1 : 0;
				//set last action done
				if (TilopayConfig::tilopay_check_is_active_HPOS()) {
					// HPOS
					$order->update_meta_data('tilopay_is_captured', $orderType);
				} else {
					update_post_meta($request_order_id, 'tilopay_is_captured', $orderType);
				}

				if (sanitize_text_field(isset($_GET['wp_cancel'])) && 'yes' == sanitize_text_field($_GET['wp_cancel'])) {
					//user cancel the process payment
					if (sanitize_text_field(isset($_GET['order']))) {

						$message_text = esc_html(__('¡Process canceled by user!', 'tilopay'));
						if (TilopayConfig::tilopay_check_is_active_HPOS()) {
							//HPOS
							$order->update_meta_data('tpay_cancel', 'yes');
						} else {
							update_post_meta(sanitize_text_field($_REQUEST['order']), 'tpay_cancel', 'yes');
						}

						TilopayConfig::tilopay_show_block_notices('error', __($message_text, 'tilopay'));
					}
				} else if (!empty($request_code_payment) && 1 == $request_code_payment) {

					$amount = $order->get_total();

					//computed hash_hmac
					$customer_computed_hash_hmac = $tilopay_getaway_instance->computed_customer_hash($request_order_id, $amount, get_woocommerce_currency(), $request_tpay_order_id, $request_code_payment, $request_auth_code_payment, $billing_email);

					//check approved order
					if (!empty($computed_hash_hmac_tilopay_server) && 64 == strlen($computed_hash_hmac_tilopay_server)) {

						/**
						 *
						 * Use hmac data to check if the response is from Tilopay or not
						 *
						 */
						if (!hash_equals($customer_computed_hash_hmac, $computed_hash_hmac_tilopay_server) || (empty($request_auth_code_payment) || strlen($request_auth_code_payment) < 6)) {

							// Generate URL for invalid order confirmation
							$checkout_url = $tilopay_getaway_instance->tpay_url_for_invalide_hash_confirmation($order, $computed_hash_hmac_tilopay_server, $customer_computed_hash_hmac, $wc_order_id);

							header('Cache-Control: no-cache, must-revalidate');
							header('Location: ' . $checkout_url, true, 307);
							exit;
						} else if (hash_equals($customer_computed_hash_hmac, $computed_hash_hmac_tilopay_server)) {

							//Check if nor already updated auth code and check if order has a status that already closed like processing mean that the payment was already done
							if (empty($existing_auth_code) && $check_unpaid_status) {
								/**
								 *
								 * If payment was approve by Tilopay
								 * Process to pudate the order status payment.
								 *
								 */
								if (TilopayConfig::tilopay_check_is_active_HPOS()) {
									//HPOS
									$order->update_meta_data('tilopay_auth_code', $request_auth_code_payment);
								} else {
									update_post_meta($request_order_id, 'tilopay_auth_code', $request_auth_code_payment);
								}
								$order->add_order_note(__('Authorization:', 'tilopay') . $request_auth_code_payment);
								$order->add_order_note(__('Code:', 'tilopay') . $request_code_payment);
								$order->add_order_note(__('Description:', 'tilopay') . $request_description);
								// translators: %s action type.
								$order->add_order_note(sprintf(__('%s Tilopay id:', 'tilopay'), $textOrderTilopay) . $request_tpay_order_id);

								$have_subscription = ((function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order)) || (function_exists('wcs_is_subscription') && wcs_is_subscription($request_order_id)));
								//Save card for Woocommerce subscriptions payments
								if ($have_subscription) {
									if (TilopayConfig::tilopay_check_is_active_HPOS()) {
										//HPOS
										$order->update_meta_data('card', $request_token_card);
									} else {
										update_post_meta($request_order_id, 'card', $request_token_card);
									}
								}

								$subscriptions = array();
								// Also store it the subscriptions for being purchased or paid the order.
								if ($have_subscription) {
									$subscriptions = wcs_get_subscriptions_for_order($order);
								} else if (function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal($order)) {
									$subscriptions = wcs_get_subscriptions_for_renewal_order($order);
								}

								if (is_array($subscriptions) && count($subscriptions) > 0) {
									foreach ($subscriptions as $order_subscription_id => $subscription) {

										$tilopay_getaway_instance->log(__METHOD__ . ':::' . __LINE__ . ', order_subscription_id' . $order_subscription_id, 'info');

										if (TilopayConfig::tilopay_check_is_active_HPOS()) {
											//HPOS
											$subscription->update_meta_data('card', $request_token_card);
											$subscription->update_meta_data('order', $request_order_id);
											$subscription->save();
											$tilopay_getaway_instance->log(__METHOD__ . ':::' . __LINE__ . ', subscription meta HPOS order_subscription_id:' . $order_subscription_id, 'info');
										} else {
											update_post_meta($order_subscription_id, 'card', $request_token_card);
											update_post_meta($order_subscription_id, 'order', $request_order_id);
											$tilopay_getaway_instance->log(__METHOD__ . ':::' . __LINE__ . ', subscription meta not HPOS order_subscription_id:' . $order_subscription_id, 'info');
										}
									}
								}

								/**
								 *
								 * Update payment status
								 * Payment status: ( 'yes' == $capture_payment ) ? payment complete : pending
								 * $tilopay_getaway_instance->tpay_get_order_status( )
								 */
								$wc_order_status = $tilopay_getaway_instance->tpay_get_order_status();
								$order->set_status($wc_order_status);
								$order->save();

								$redirectOrder = $tilopay_getaway_instance->get_return_url($order); // . 'order-received/' . $request_order_id . '/?key=wc_order_' . $returnData;

								//Check if car not ready empty
								if ($woocommerce->cart->is_empty()) {
									$woocommerce->cart->empty_cart();
								}

								//Redirect to order details
								header('Cache-Control: no-cache, must-revalidate');
								header('Location: ' . $redirectOrder, true, 307);

								wp_safe_redirect(esc_url($redirectOrder));
								exit;
							} else {

								//Check if car not ready empty
								if (!WC()->cart->is_empty()) {
									$woocommerce->cart->empty_cart();
								}

								$redirectOrder = $tilopay_getaway_instance->get_return_url($order); // . 'order-received/' . $request_order_id . '/?key=wc_order_' . $returnData;
								//Redirect to order details
								header('Cache-Control: no-cache, must-revalidate');
								header('Location: ' . $redirectOrder, true, 307);

								wp_safe_redirect(esc_url($redirectOrder));
								exit;
							}
						} else {

							$error_message_validation = __('Description:', 'tilopay') . $request_description;
							//no hash or not equals
							$order->add_order_note($error_message_validation);
							$order->save();
							TilopayConfig::tilopay_show_block_notices('error', 'Error: ' . $error_message_validation);

							$checkout_url = wc_get_checkout_url();
							$pos = strpos($checkout_url, '?');
							if (false === $pos) {
								$checkout_url = $checkout_url . '?message_error=' . $error_message_validation;
							} else {
								$checkout_url = $checkout_url . '&message_error=' . $error_message_validation;
							}
							header('Cache-Control: no-cache, must-revalidate');
							header('Location: ' . $checkout_url, true, 307);
							exit;
						}
					} else {

						// Generate URL for invalid order confirmation
						$checkout_url = $tilopay_getaway_instance->tpay_url_for_invalide_hash_confirmation($order, $computed_hash_hmac_tilopay_server, $customer_computed_hash_hmac, $wc_order_id);

						header('Cache-Control: no-cache, must-revalidate');
						header('Location: ' . $checkout_url, true, 307);
						exit;
					} //. end check hash
				} else if ('Pending' == $request_code_payment && empty($existing_auth_code)) {

					//Update order status
					$order->set_status('wc-on-hold');

					if (TilopayConfig::tilopay_check_is_active_HPOS()) {
						//HPOS
						$order->update_meta_data('tilopay_auth_code', $request_code_payment);
					} else {
						update_post_meta($request_order_id, 'tilopay_auth_code', $request_code_payment);
					}
					$order->add_order_note(__('Code:', 'tilopay') . $request_code_payment);
					$order->add_order_note(__('Description:', 'tilopay') . $request_description);
					// translators: %s action type.
					$order->add_order_note(sprintf(__('%s Tilopay id:', 'tilopay'), $textOrderTilopay) . $request_tpay_order_id);
					$order->save();

					//Check if car not ready empty
					if (!WC()->cart->is_empty()) {
						WC()->cart->empty_cart();
					}
					//Redirect to order details
					wp_safe_redirect(esc_url($tilopay_getaway_instance->get_return_url($order)));
					exit;
				} else {

					/**
					 * The payment was not approved by Tilopay
					 *
					 */
					$tilopay_getaway_instance->log(__METHOD__ . ':::' . __LINE__ . ', else, not approved:' . print_r($_GET, true), 'info');

					$order->add_order_note(__('Order with failed payment', 'tilopay'));
					if (!empty($request_code_payment)) {
						$order->add_order_note(__('Code:', 'tilopay') . $request_code_payment);
					}
					$order->add_order_note(__('Description:', 'tilopay') . $request_description);

					//Update order status
					$order->set_status('wc-failed');
					$order->save();

					//if SINPEMOVIL error mean is partial payment
					if ('SINPEMOVIL' == $selected_method) {
						// translators: %1$s the message from tilopay, %2$s the order number.
						$request_description = sprintf(__('%1$s, contact the seller to complete your order no.%2$s. If you try to pay again, your payment will be rejected.', 'tilopay'), $request_description, $request_order_id);
					}

					$checkout_url = wc_get_checkout_url();
					$pos = strpos($checkout_url, '?');
					if (false === $pos) {
						$checkout_url = $checkout_url . '?message_error=' . $request_description . '&from=' . __LINE__;
					} else {
						$checkout_url = $checkout_url . '&message_error=' . $request_description . '&from=' . __LINE__;
					}

					header('Cache-Control: no-cache, must-revalidate');
					//header('Location: ' . $checkout_url, true, 307);
					wp_safe_redirect(esc_url($checkout_url));
					exit;
				}
			}
		}
	}

	/**
	 * Checks and forces the sending of specific emails based on the order status.
	 *
	 * @param object $order The order object.
	 * @param string $wc_order_status The WooCommerce order status.
	 */
	function tilopay_force_email_to_sent($order, $wc_order_status) {

		try {
			$order_id = $order->get_id();

			(new WCTilopay())->log(__METHOD__ . ':::' . __LINE__ . ', send email, order_id:' . $order_id . ', wc_order_status:' . $wc_order_status, 'info');

			if (in_array($wc_order_status, ['processing', 'pending'])) {

				// Force sending of the "New Order" email
				WC()->mailer()->emails['WC_Email_New_Order']->trigger($order_id);

				(new WCTilopay())->log(__METHOD__ . ':::' . __LINE__ . ', _new_order_email_sent, order_id:' . $order_id, 'info');
			}

			if ('processing' == $wc_order_status) {
				// Force sending of the "Order in process" email
				WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order_id);

				(new WCTilopay())->log(__METHOD__ . ':::' . __LINE__ . ', send email WC_Email_Customer_Processing_Order, order_id:' . $order_id . ', wc_order_status:' . $wc_order_status, 'info');
			}

			if ('pending' == $wc_order_status) {

				// Force sending of the "Order pending" email
				WC()->mailer()->emails['WC_Email_Customer_On_Hold_Order']->trigger($order_id);

				(new WCTilopay())->log(__METHOD__ . ':::' . __LINE__ . ', send email WC_Email_Customer_On_Hold_Order, order_id:' . $order_id . ', wc_order_status:' . $wc_order_status, 'info');
			}
		} catch (\Throwable $th) {
			//throw $th;
			$this->log(__METHOD__ . ':::' . __LINE__ . 'Throwable message error:' . $th->getMessage() . ', file:' . $th->getFile() . ', line:' . $th->getLine(), 'error');
		}
	}


	/**
	 * Locates the block notice template for the given template name and template path.
	 *
	 * @param string $template The current template path.
	 * @param string $template_name The name of the template to locate.
	 * @param string $template_path The path to the template.
	 * @return string The updated template path if the plugin template exists, otherwise the original template path.
	 */
	function tilopay_locate_block_notice_template($template, $template_name, $template_path) {
		$plugin_path = PLUGIN_ABS_PATH_TPAY . 'templates/';

		if (file_exists($plugin_path . $template_name)) {
			$template = $plugin_path . $template_name;
		}

		return $template;
	}

	/**
	 * Registers WooCommerce High-Performance order storage compatibility.
	 */
	public static function tilopay_gateway_high_performance_order_storage_support() {
		// Declaring extension (in)compatibility with WOO HPOS
		if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', plugin_basename(TPAY_MAIN_FILE), true);
		}
		// Check if the required class exists
		if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
			// Declare compatibility for 'cart_checkout_blocks'
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', plugin_basename(TPAY_MAIN_FILE), true);
		}
	}

	/**
	 * Registers WooCommerce Blocks integration.
	 */
	public static function tilopay_woocommerce_gateway_block_support() {
		if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {

			// Include the custom Blocks Checkout class
			require_once PLUGIN_ABS_PATH_TPAY . 'includes/WCTilopayBlockCheckout.php';

			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function (\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
					$payment_method_registry->register(new WC_Tilopay_Blocks());
				}
			);
		}
	}

	/**
	 * Adds the Tilopay settings menu in the WooCommerce menu.
	 *
	 * @return void
	 */
	public function tpay_add_menu() {
		$this->page_id = add_submenu_page(
			'woocommerce',
			__('Settings', 'tilopay') . ' Tilopay',
			__('Settings', 'tilopay') . ' Tilopay',
			'manage_woocommerce',
			'wc-settings&tab=checkout&section=tilopay',
			array($this, '')
		);
	}

	/**
	 * Helper function to hook, check if Tilopay is enable or deseable
	 */
	public function tilopay_gateway_payment_status($available_gateways) {
		if (isset($available_gateways['tilopay']) && is_object($available_gateways['tilopay'])) {
			if ('no' == $available_gateways['tilopay']->enabled) {
				unset($available_gateways['tilopay']);
			}
		}
		return $available_gateways;
	}

	//Helper function to hook
	public function tpay_plugin_cancel_tilopay() {
		$log = new \WC_Logger();
		$log->add('test', 'ENTRE');
		if (!wp_next_scheduled('tpay_my_cron_tilopay')) {
			$log->add('test', 'ENTRE');
			wp_schedule_event(current_time('timestamp'), 'every_three_minutes', 'tpay_my_cron_tilopay');
		}
	}

	//Helper function to hook
	public function tpay_my_process_tilopay() {
		$orders = wc_get_orders(array(
			'status' => 'pending',
			'return' => 'ids',
			'limit' => -1,
		));
		foreach ($orders as $data) {
			$logger = new \WC_Logger();
			if (TilopayConfig::tilopay_check_is_active_HPOS()) {
				// HPOS
				$meta_field_data = $data->get_meta('tpay_cancel', true);
			} else {
				$meta_field_data = get_post_meta($data, 'tpay_cancel')[0] ? get_post_meta($data, 'tpay_cancel')[0] : '';
			}
			if ('modal' == $meta_field_data) {
				$order = wc_get_order($data);
				$wc_order_id = $order->get_id();

				$order->add_order_note(__('Order canceled when closing the payment method', 'tilopay'));
				//Update order status
				$order->set_status('wc-cancelled');
				$order->save();
			}
		}
	}

	//Helper function to hook
	public function tpay_add_cron_recurrence_interval($schedules) {

		$schedules['every_three_minutes'] = array(
			'interval' => 180,
			'display' => __('Every 3 minutes', 'tilopay')
		);

		return $schedules;
	}

	/**
	 * Adds a settings link to the plugins.php page
	 *
	 * @param  array $links Array of links.
	 * @return array        Array of links with the added settings link.
	 */
	public function settings_link($links) {

		$settings_link = '<a target="_blank" rel="noopener"  href="https://tilopay.com/documentacion/plataforma-woocommerce">' . __('Docs', 'tilopay') . '</a>';
		array_unshift($links, $settings_link);

		$settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=tilopay">' . __('Settings', 'tilopay') . '</a>';
		array_unshift($links, $settings_link);

		return $links;
	}

	/**
	 * ************************************************
	 * ************************************************
	 * Woocommerce class init function hook helper ****
	 * ************************************************
	 * ************************************************
	 */

	// Register the gateway in WC
	public function add_woocommerce_tilopay_gateway($methods) {
		if (class_exists('Tilopay\\WCTilopay')) {
			$methods[] = new WCTilopay();
		}

		return $methods;
	}

	// Define the woocommerce_credit_card_form_fields callback
	public function tpay_filter_woocommerce_credit_card_form_fields() {
		if (sanitize_text_field(isset($_REQUEST['response_data']))) {
			if (isset($_REQUEST['response_data']) && 'found' == $_REQUEST['response_data'] && sanitize_text_field(isset($_REQUEST['order_id']))) {
				$order_id = sanitize_text_field($_REQUEST['order_id']);

				if (TilopayConfig::tilopay_check_is_active_HPOS()) {
					// HPOS
					$order = wc_get_order($order_id);
					$tpay_url_payment_form = $order->get_meta('tilopay_html_form', true);
				} else {
					$tpay_url_payment_form = get_post_meta($order_id, 'tilopay_html_form')[0];
				}
				//check if have html
				if (isset($tpay_url_payment_form) && '' != $tpay_url_payment_form) {
					wp_safe_redirect(esc_url($tpay_url_payment_form));
					exit;
				}
			}
		}
	}

	//Change order status
	public function tpay_order_status_changed($order_id) {
		/**
		 * Function tpay_process_payment_modification( $order_id, $type, $total )
		 *$type:
		 * 1 = Capture ( captura )
		 * 2 = Refund ( reembolso )
		 * 3 = Reversal ( reverso )
		 */

		$wc_tilopay = new WCTilopay();

		$wc_tilopay->log(__METHOD__ . ':::' . __LINE__ . ', order_id:' . $order_id, 'warning');
		if (!$order_id) {
			return;
		}

		$order = wc_get_order($order_id);
		if (!$order) {
			return;
		}

		$wc_order_id = $order->get_id();
		$payment_method = $order->get_payment_method();

		// If payment method is not tilopay, stop processing
		if ('tilopay' !== $payment_method || empty($payment_method)) {
			return;
		}

		$getResponse = false;
		$type = '';

		if (TilopayConfig::tilopay_check_is_active_HPOS()) {
			// HPOS
			$capture = $order->get_meta('tilopay_is_captured', true);
		} else {
			$capture = key_exists(0, get_post_meta($order_id, 'tilopay_is_captured')) ? get_post_meta($order_id, 'tilopay_is_captured')[0] : null;
		}

		$wc_tilopay->log(__METHOD__ . ':::' . __LINE__ . ', order_id:' . $order_id . ', capture:' . $capture . ', order_status:' . $order->get_status(), 'warning');

		if (in_array($order->get_status(), ['processing', 'wc-processing']) && 0 == $capture) {
			//Process capture.
			$type = '1'; // 1 capture,
			$getResponse = $wc_tilopay->tpay_process_payment_modification($order_id, '1', $order->get_total());
		}
		/*
			if ( in_array($order->get_status(), ['refunded', 'wc-refunded']) && $capture == 1 ) {
				$wc_tilopay = new WCTilopay();
				$wc_tilopay->tpay_process_payment_modification( $order_id, "2" );
			}
			if ( in_array($order->get_status(), ['refunded', 'wc-refunded']) && $capture == 0 ) {
				$wc_tilopay = new WCTilopay();
				$wc_tilopay->tpay_process_payment_modification( $order_id, "3" );
			}
			*/
		//--------------------verify if exists capture------------------
		$wc_tilopay->log(__METHOD__ . ':::' . __LINE__ . ', order_id:' . $order_id . ', capture:' . $capture . ', order_status:' . $order->get_status() . ', type:' . $type . ', getResponse:' . print_r($getResponse, true), 'warning');
		if (!empty($type)) {
			//Set not from API Response
			if (false !== $getResponse && isset($getResponse->ReasonCode) && in_array($getResponse->ReasonCode, ['1', '1101'])) {
				$request_tpay_order_id = (isset($getResponse->transactionId)) ? $getResponse->transactionId : '';
				$textOrderTilopay = (1 == $type) ? __('Capture', 'tilopay') : '';
				//Set last actions done = capture
				if (TilopayConfig::tilopay_check_is_active_HPOS()) {
					// HPOS
					$order->update_meta_data('tilopay_is_captured', 1);
				} else {
					update_post_meta($order_id, 'tilopay_is_captured', 1);
				}
				// translators: %s action type.
				$order->add_order_note(sprintf(__('%s Tilopay id:', 'tilopay'), $textOrderTilopay) . $request_tpay_order_id);
				$order->add_order_note(__('Result:', 'tilopay') . $getResponse->ReasonCodeDescription);

				//Update order status
				$order->set_status('wc-processing');
				$order->save();

				return true;
			} else {
				//Rejected
				$errorResponse = (false !== $getResponse && isset($getResponse->ReasonCodeDescription))
					? $getResponse->ReasonCodeDescription
					: __('Connection error with TILOPAY, contact sac@tilopay.com.', 'tilopay');

				// translators: %s the order number.
				$errorNote = sprintf(__('Error, the refund of the order no.%s could not be made.', 'tilopay'), $order_id);
				$errorNote = (!empty($errorResponse)) ? $errorNote . ' Error Tilopay:' . $errorResponse : $errorNote;
				$order->add_order_note($errorNote);
				$order->set_status('wc-failed');
				$order->save();
			}
		}
	}

	/*
			// define the woocommerce_order_partially_refunded callback
	function tpay_woocommerce_order_partially_refunded($order_get_id, $refund_get_id) {
		$order_id=$order_get_id;
		if ( $order_id == "" ) {
			return;
		}
		if ( in_array($order->get_status(), ['processing', 'wc-processing']) ) {
			$wc_tilopay = new WCTilopay();
			$wc_tilopay->tpay_process_payment_modification( $order_id, "2" );
		}
		if ( in_array($order->get_status(), ['pending', 'wc-pending']) ) {
			$wc_tilopay = new WCTilopay();
			$wc_tilopay->tpay_process_payment_modification( $order_id, "3" );
		}
	};
			// define the woocommerce_order_fully_refunded callback
	function tpay_woocommerce_order_fully_refunded(  $order_get_id, $refund_get_id  ) {
		// make action magic happen here...
	};
		 */

	public function tpay_woocommerce_order_refunded($order_get_id, $refund_id) {

		/**
		 * Function tpay_process_payment_modification( $order_id, $type, $total )
		 * $type:
		 * 1 = Capture ( captura )
		 * 2 = Refund ( reembolso )
		 * 3 = Reversal ( reverso )
		 */

		//new instance
		$wc_tilopay = new WCTilopay();

		$wc_tilopay->log(__METHOD__ . ':::' . __LINE__ . ', order_get_id:' . $order_get_id . ', refund_get_id:' . $refund_id, 'warning');

		$order_id = $order_get_id;
		if ('' == $order_id) {
			$wc_tilopay->log(__METHOD__ . ':::' . __LINE__ . ', order_id null, order_get_id:' . $order_get_id . ', refund_get_id:' . $refund_id, 'warning');
			return;
		}

		// Get original order
		$order = wc_get_order($order_id);
		if (!$order) {
			$wc_tilopay->log(__METHOD__ . ':::' . __LINE__ . ', order null, order_get_id:' . $order_get_id . ', refund_get_id:' . $refund_id, 'warning');
			return;
		}

		$wc_tilopay->log(__METHOD__ . ':::' . __LINE__ . ', order null, order_get_id:' . $order_get_id . ', order:' . print_r($order, true), 'warning');

		//Get refund order
		$refund = wc_get_order($refund_id);
		if (!$refund) {
			$wc_tilopay->log(__METHOD__ . ':::' . __LINE__ . ', refund null, order_get_id:' . $order_get_id . ', refund_get_id:' . $refund_id, 'warning');
			return;
		}

		//Get last action done
		if (TilopayConfig::tilopay_check_is_active_HPOS()) {
			// HPOS
			$is_captured = $order->get_meta('tilopay_is_captured', true);
		} else {
			//Get if capture original order
			$is_captured = get_post_meta($order_id, 'tilopay_is_captured', true);
		}

		$getResponse = false;
		$type = '';

		//Check order status tilopay_is_captured
		if (in_array($order->get_status(), ['processing', 'wc-processing']) && '1' == $is_captured) {
			//2 = Refund ( reembolso )
			$getResponse = $wc_tilopay->tpay_process_payment_modification($order_id, '2', $refund->get_amount());
			$type = 2;
		}
		if (in_array($order->get_status(), ['pending', 'wc-pending']) && '0' == $is_captured) {
			//3 = Reversal ( reverso )
			$getResponse = $wc_tilopay->tpay_process_payment_modification($order_id, '3', $refund->get_amount());
			$type = 3;
		}
		if (in_array($order->get_status(), ['refunded', 'wc-refunded']) && '1' == $is_captured) {
			//2 = Refund ( reembolso )
			$getResponse = $wc_tilopay->tpay_process_payment_modification($order_id, '2', $refund->get_amount());
			$type = 2;
		}
		if (in_array($order->get_status(), ['refunded', 'wc-refunded']) && '0' == $is_captured) {
			//3 = Reversal ( reverso )
			$getResponse = $wc_tilopay->tpay_process_payment_modification($order_id, '3', $refund->get_amount());
			$type = 3;
		}

		$wc_tilopay->log(__METHOD__ . ':::' . __LINE__ . ', order_get_id:' . $order_get_id . ', refund_get_id:' . $refund_id . ', is_captured:' . $is_captured . 'type:' . $type . 'order->get_status:' . $order->get_status() . ', getResponse:' . print_r($getResponse, true), 'warning');

		if (!empty($type)) {
			$tpay_order_id = (isset($getResponse->transactionId)) ? $getResponse->transactionId : '';
			//check if capture to set refund if not set reverse
			$textOrderTilopay = (1 == $is_captured) ? __('Refund', 'tilopay') : __('Reverse', 'tilopay');
			//Set not from API Response
			if (false !== $getResponse && isset($getResponse->ReasonCode) && in_array($getResponse->ReasonCode, ['1', '1101'])) {
				// translators: %s action type.
				$order->add_order_note(sprintf(__('%s Tilopay id:', 'tilopay'), $textOrderTilopay) . $tpay_order_id);
				$order->add_order_note(__('Code:', 'tilopay') . $getResponse->ReasonCode);
				$order->add_order_note(__('Result:', 'tilopay') . $getResponse->ReasonCodeDescription);

				$wc_order_id = $order->get_id();
				//Update order status
				$order->set_status('wc-refunded');
				$order->save();

				return true;
			} else {
				//Rejected
				$haveMessage = isset($getResponse->message) ? $getResponse->message : '';
				$errorResponse = (false !== $getResponse && isset($getResponse->ReasonCodeDescription))
					? $getResponse->ReasonCodeDescription
					: $haveMessage;

				$errorResponse = (empty($errorResponse))
					? __('Connection error with TILOPAY, contact sac@tilopay.com.', 'tilopay')
					: $errorResponse;

				// translators: %s the order number.
				$errorNote = sprintf(__('Error, the refund of the order no.%s could not be made.', 'tilopay'), $order_id);
				$errorNote = (!empty($errorResponse)) ? $errorNote . ' Error Tilopay:' . $errorResponse : $errorNote;

				$order->add_order_note($errorNote);
			}
		}
		$wc_tilopay->log(__METHOD__ . ':::' . __LINE__ . ', last line conditions, order_get_id:' . $order_get_id . ', refund_get_id:' . $refund_id, 'warning');
		return;
	}

	/**
	 * Admin script to upload logo, only load at WC wc-settings page, js and css
	 */
	public function enqueuing_admin_config_payment_scripts() {
		//Add here the script to load in whole administration or the specific page conditions
		//Only for Woocommerce Settings
		if (isset($_GET['page']) && 'wc-settings' == $_GET['page']) {
			wp_enqueue_media();
			$config_payment_ver = gmdate('ymd-Gis');
			wp_register_script('tilopay-config-payment', WP_PLUGIN_URL . '/tilopay/assets/js/tilopay-config-payment.js', array('jquery'), $config_payment_ver, true);
			wp_enqueue_script('tilopay-config-payment');
			//multiselect
			$multiselect_dropdown_ver = gmdate('ymd-Gis');
			wp_register_script('tilopay-config-payment-multiselect', WP_PLUGIN_URL . '/tilopay/assets/js/multiselect-dropdown.js', array('jquery'), $multiselect_dropdown_ver, true);
			wp_enqueue_script('tilopay-config-payment-multiselect');
			wp_localize_script('tilopay-config-payment-multiselect', 'traslateVar', array(
				'selectAll' => __('Select all', 'tilopay'),
				'search' => __('Search', 'tilopay'),
				'select' => __('Select', 'tilopay'),
				'selected' => __('Selected', 'tilopay'),
				'remove' => __('Remove', 'tilopay')
			));
			//Pass parameter to the script js, here we are passing plugins_url
			wp_localize_script('tilopay-config-payment', 'variableSet', array(
				'pluginsUrl' => plugins_url(),
				'removeIconButtonText' => __('Remove icon', 'tilopay'),
				'useTiloPayIcon' => __('Use TILOPAY icon', 'tilopay'),
				'swalTitel' => __('Are you sure to switch to authorization and partial capture mode?', 'tilopay'),
				'swalBody' => __('Once you receiving an order in your store with the authorization and partial capture mode, Tilopay only authorizes the transactions and the order remains in the Pending Payment status. To capture the amount and complete the transaction, an administrator can review and change the order if necessary, then change the order status to Processing. In case of not making the change of status to Processing, the transaction will be automatically canceled after 7 days. Are you sure you want to continue?', 'tilopay'),
				'swalBtnCancel' => __('No, cancel', 'tilopay'),
				'swalBtnOk' => __('Yes, i understand', 'tilopay'),
				'swalNoChange' => __('Excellent, the capture mode was not changed.', 'tilopay'),
				'swalChange' => __('Excellent, the capture mode was changed to authorization and partial capture.', 'tilopay'),
			));

			//CSS
			$my_admincss_ver = gmdate('ymd-Gis', filemtime(TPAY_PLUGIN_DIR . '/assets/css/tilopay-config-payment-admin.css'));
			wp_register_style('tilopay-config-payment-admin', WP_PLUGIN_URL . '/tilopay/assets/css/tilopay-config-payment-admin.css', false, $my_admincss_ver);
			wp_enqueue_style('tilopay-config-payment-admin');

			$sweetalert_ver = gmdate('ymd-Gis');
			wp_register_script('sweetalert-TYP', 'https://unpkg.com/sweetalert/dist/sweetalert.min.js', null, $sweetalert_ver, true);
			wp_enqueue_script('sweetalert-TYP');
		} //.End only for Woocommerce Settings
	}

	/**
	 * Load tilopay front scripts, js and css
	 */
	public function load_tilopay_front_scripts($hook) {
		// we need JavaScript to process a token only on cart/checkout pages, right?
		if (is_checkout() || isset($_GET['pay_for_order'])) {
			// create my own version codes
			//$my_modaljs_ver = gmdate( 'ymd-Gis', filemtime( TPAY_PLUGIN_DIR . '/assets/js/jquery.modal.min.js' ) );
			//$my_css_ver = gmdate( 'ymd-Gis', filemtime( TPAY_PLUGIN_DIR . '/assets/css/jquery.modal.min.css' ) );
			$my_admincss_ver = gmdate('ymd-Gis', filemtime(TPAY_PLUGIN_DIR . '/assets/css/admin.css'));

			//wp_enqueue_script( 'tilopay-modaljs', WP_PLUGIN_URL . '/tilopay/assets/js/jquery.modal.min.js', array(), $my_modaljs_ver );
			//wp_register_style( 'tilopay-modal-frontcss', WP_PLUGIN_URL . '/tilopay/assets/css/jquery.modal.min.css', false, $my_css_ver );
			wp_enqueue_style('tilopay-modal-frontcss');
			wp_register_style('tilopay-frontcss', WP_PLUGIN_URL . '/tilopay/assets/css/admin.css', false, $my_admincss_ver);
			wp_enqueue_style('tilopay-frontcss');
		} else {
			return;
		}
	}

	//load_plugin_textdomain
	public function tilopay_on_init() {

		$path = dirname(TPAY_PLUGIN_BASENAME) . '/languages/';
		load_plugin_textdomain('tilopay', false, $path);
	}

	//load lang
	public function load_tilopay_textdomain($mofile, $domain) {
		if ('tilopay' === $domain && false !== strpos($mofile, WP_LANG_DIR . '/plugins/')) {
			$locale = apply_filters('plugin_locale', determine_locale(), $domain);
			$mofile = WP_PLUGIN_DIR . '/' . TPAY_PLUGIN_NAME . '/languages/' . $domain . '-' . $locale . '.mo';
		}
		return $mofile;
	}

	//Route to check form is valide
	public function register_tilopay_validation_form_route() {
		$isNative = (new WCTilopay())->isNativePayment();
		if ($isNative) {
			register_rest_route(
				'tilopay/v1',
				'/tpay_validate_checkout_form_errors',
				array(
					'methods' => 'POST',
					'callback' => array($this, 'tpay_validate_form_request'),
					'permission_callback' => '__return_true'
				)
			);
		}
	}

	//Route callback handler
	public function tpay_validate_form_request() {
		// Call validate_fields to validate errors
		$getResponse = (new WCTilopay())->validate_fields();

		// Response JSON
		return rest_ensure_response($getResponse);
	}
}
