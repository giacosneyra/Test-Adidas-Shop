
import { sprintf, __ } from '@wordpress/i18n';
//const { PAYMENT_STORE_KEY } = window.wc.wcBlocksData;
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { useState, useEffect } from 'react';
import SavedTokenComponent from './SavedTokenComponent';

//Import FE js to use Tilopay SDK
import '../../../assets/js/block-tilopay-checkout.js';
import { PaymentMethodOptionTilopay } from './payment-method.js';
import { AddNewCardFormTilopay } from './payment-card.js';
import { SinpeMovilTilopay } from './payment-sinpe-movil.js';
import { useDispatch, dispatch, select, useSelect } from '@wordpress/data';
import { WcNoticeBlock } from './wc-notice-block.js';
import { SdkHtmlForm } from './sdk-html-form.js';
import { HandlerPayment } from './handler-payment.js';
import { PaymentYappyTilopay } from './payment-yappy.js';

const settings = getSetting('tilopay_data', {});
const { sdk_init_payload, tpay_key } = settings;

const defaultLabel = __(
	'Tilopay',
	'tilopay'
);

const label = decodeEntities(settings.title) || defaultLabel;

/**
 * Tilopay component
 */
const TilopayComponent = (props) => {

	if (settings.tpay_redirect !== 'yes') {


		const [checkoutData, setCheckoutData] = useState(undefined);
		const wc_selected_method = select('wc/store/payment').getActivePaymentMethod();
		const wc_payment_result = select('wc/store/payment').getPaymentResult();
		const wc_order_id = sdk_init_payload.wooSessionTpay; //select('wc/store/checkout').getOrderId(); //
		const wc_redirectUrl = select('wc/store/checkout').getRedirectUrl();
		const wc_checkoutStatus = select('wc/store/checkout').getCheckoutStatus();

		const [selectedPaymentMethod, setSelectedPaymentMethod] = useState(undefined);
		const [selectedCard, setSelectedCard] = useState('otra');
		const [loaderTpay, setLoaderTpay] = useState('flex');
		const [removeHideInput, setRemoveHideInput] = useState(false);

		const [paymentData, setPaymentData] = useState({
			tlpy_cc_number: '',
			tlpy_cc_expiration_date: '',
			tlpy_cvv: '',
			cards: 'newCard',
			woo_session_tilopay: sdk_init_payload.wooSessionTpay,
			pay_sinpemovil_tilopay: false,
			tpay_save_card: 'off',
			tpay_can_make_payment: true,
			process_with_apple_pay: 0,
		});

		const [paymentError, setPaymentError] = useState(undefined)

		const [sdkInitResponse, setSdkInitResponse] = useState(undefined);
		const [orderId, setOrderId] = useState(undefined)
		const [activeModal, setActiveModal] = useState(false);

		const [applePayProcessed, setApplePayProcessed] = useState(false);

		const { eventRegistration, emitResponse, billing, cartData, onSubmit, components } = props;
		const { onPaymentSetup, onCheckoutFail, onCheckoutSuccess, onCheckoutValidation, onCheckoutValidationBeforeProcessing } = eventRegistration;
		const { noticeContexts, responseTypes } = emitResponse;

		const cartTotal = billing.cartTotal.value / 100;
		const currency = billing.currency.code;
		const { first_name, last_name, company, address_1, address_2, city, state, postcode, country, email, phone } = billing.billingAddress;
		const cartItems = cartData.cartItems; // Search in array type subscription
		// Search in array to check if type subscription
		const have_subscription = cartItems.find(item => item.type === 'subscription') !== undefined;

		const { setValidationErrors, clearValidationError } = useDispatch('wc/store/validation');

		// Set SDK Settings data fro init
		let initSdkSettings = {
			token: sdk_init_payload.token ?? false,
			currency: currency,
			language: sdk_init_payload.language ?? 'EN',
			amount: cartTotal ?? 0,
			amount_sinpe: cartTotal > 0 ? Math.ceil(cartTotal) : 0,
			billToFirstName: first_name,
			billToLastName: last_name,
			billToAddress: address_1,
			billToAddress2: address_2,
			billToCity: city,
			billToState: state,
			billToZipPostCode: postcode,
			billToCountry: country,
			billToTelephone: phone,
			billToEmail: email ?? '',
			orderNumber: wc_order_id,
			capture: settings.tpay_capture === 'yes' ? 1 : 0,
			redirect: settings.tpay_checkout_redirect,
			subscription: 0,
			platform: "woocommerce",
			platform_reference: sdk_init_payload.platform_reference,
			userDataIn: sdk_init_payload.userDataIn ?? 0,
			urlTilopay: sdk_init_payload.urlTilopay,
			Key: settings.tpay_key,
			tpayPluginUrl: sdk_init_payload.tpayPluginUrl,
			wooSessionTpay: sdk_init_payload.wooSessionTpay,
			//applePayCallback: applePayCallback,
		};

		const { applePayIntegrationTilopay, placeHolderBtnWooCommerceOverride } = HandlerPayment(initSdkSettings, sdkInitResponse, setPaymentData, setPaymentError, selectedPaymentMethod, setApplePayProcessed);

		// Helper functions to load form
		useEffect(() => {
			if (typeof selectedPaymentMethod !== 'undefined' && selectedPaymentMethod?.type === "card") {
				setRemoveHideInput(true)
			}

			if (typeof cleanSinpeMovilInterval === 'function') {
				cleanSinpeMovilInterval();
			} else {
				console.log('cleanSinpeMovilInterval is undefined.');
			}

		}, [selectedPaymentMethod]);


		//Apple Pay Handler btn place order payment
		useEffect(() => {
			if (typeof selectedPaymentMethod !== 'undefined') {
				placeHolderBtnWooCommerceOverride();
			}
		}, [selectedPaymentMethod, wc_checkoutStatus]);

		// Set validation errors for sinpemovil
		useEffect(() => {

			if (selectedPaymentMethod?.type === "sinpemovil") {
				// setValidationErrors({
				// 	'sinpemovil-waiting': {
				// 		message: 'Waiting for Sinpe móvil payment...',
				// 		hidden: false,
				// 	},
				// });
				if (typeof paymentError == 'undefined') {
					//setPaymentError('Waiting for Sinpe móvil payment...')
				}
				return;
			}


			if (paymentError == 'Waiting for Sinpe móvil payment...') {
				//setPaymentData(undefined);
			}

			//clearValidationError('sinpemovil-waiting');
		}, [
			clearValidationError,
			setValidationErrors,
			selectedPaymentMethod,
		]);

		// Set checkout data
		useEffect(() => {
			setCheckoutData({
				order_id: wc_order_id,
				first_name,
				last_name,
				company,
				address_1,
				address_2,
				city,
				state,
				zip_code: postcode,
				country,
				email,
				phone,
				currency,
				cart_total: cartTotal,
				have_subscription,
				checkout_url: settings.tpay_checkout_redirect,
				tpayPluginUrl: sdk_init_payload.tpayPluginUrl
			});
		}, []);

		// Call getPaymentMethodOptions on load
		useEffect(() => {
			setTimeout(async () => {
				if (email !== null && email !== sdk_init_payload.email && paymentData.pay_sinpemovil_tilopay !== 'sinpemovil') {
					await getPaymentMethodOptions();
				}
			}, 100);

		}, [email]);

		// getPaymentMethodOptions on load
		const getPaymentMethodOptions = async () => {
			setLoaderTpay('flex');
			if (initSdkSettings.token === false) {
				setPaymentError(__("There seems to be an issue with this website's integration with Tilopay, please inform the seller to complete your purchase.", 'tilopay'));
			} else {
				const responseSdk = await getSdkInit(initSdkSettings);

				const { sinpemovil, environment, message } = responseSdk;
				if (message == "Success") {
					setSdkInitResponse(responseSdk);
				} else {
					if (typeof message !== 'undefined' && message !== '') {
						setPaymentError(message);
					}
				}
			}
			setLoaderTpay('none');
		}

		//validations
		//https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/third-party-developers/extensibility/data-store/validation.md
		useEffect(() => {
			//https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/internal-developers/block-client-apis/checkout/checkout-flow-and-events.md
			const unsubscribe = onPaymentSetup(async () => {
				// Here we can do any processing we need, and then emit a response.
				// For example, we might validate a custom field, or perform an AJAX request, and then emit a response indicating it is valid or not.
				closeTilopayErrorMessage();
				if (paymentData !== null && Object.keys(paymentData).length > 0) {

					paymentData.tpay_woo_checkout_nonce = settings.tpay_nonce;

					if (typeof selectedPaymentMethod !== 'undefined' && selectedPaymentMethod.type === 'yappy') {
						if (paymentData.tlpy_yappy_phone === '') {
							setPaymentError(__("Please enter a valid phone number", 'tilopay'));
							return {
								type: emitResponse.responseTypes.ERROR,
								data: {
									error: true,
									message: __("Please enter a valid phone number", 'tilopay'),
								},
							};
						} else {
							setPaymentError(undefined);
						}

					}
					if (!applePayProcessed && typeof selectedPaymentMethod !== 'undefined' && selectedPaymentMethod.type === 'applepay') {

						const applePayPayload = await applePayIntegrationTilopay(); // Asegúrate de que los datos de Apple Pay estén listos
						if (applePayPayload !== false) {
							paymentData.payload_apple_pay = applePayPayload;
							paymentData.process_with_apple_pay = 1;
							setApplePayProcessed(true);
						} else {
							// debuggingJsFromWoocommerce(`onpaymentsetup applePayPayload ERROR->${ applePayProcessed },  paymentData->${ JSON.stringify(applePayPayload) }`);
							// Refresh Payment Options
							await getPaymentMethodOptions();
							return {
								type: emitResponse.responseTypes.ERROR,
								message: __('Process payment with Apple Pay failed. Please try again, if the error persists, refresh the page.', 'tilopay'),
							};
						}
					}

					// Only if not Apple Pay
					if (paymentData.process_with_apple_pay === 0) {

						if (paymentData.tpay_env === 'PROD' && paymentData.pay_sinpemovil_tilopay === true && checkoutData.have_subscription === true) {
							setPaymentError(__('You cannot pay subscriptions with SINPE Movíl, please pay with a credit or debit card', 'tilopay'));
						} else {
							setPaymentError(undefined);
						}

						if (paymentData.tpay_env == 'PROD' && paymentData.pay_sinpemovil_tilopay === false) {

							paymentData.tlpy_cc_number = (paymentData.tlpy_cc_number) ? '...-' + String(paymentData.tlpy_cc_number.slice(-4)) : ''
							//paymentData.tlpy_cvv = (paymentData.tlpy_cvv) ? '000' : ''

							let emitErrorValidations = true;

							if (paymentData.cards !== 'newCard' && paymentData.cards !== '') {
								// Card save from Tilopay
								if (paymentData.token_hash_code_tilopay === '') {
									emitErrorValidations = true
								} else {
									emitErrorValidations = false;
								}
							} else if (paymentData.cards === 'newCard' && paymentData.cards !== '') {
								// New card
								if (paymentData.token_hash_card_tilopay === '' || paymentData.token_hash_code_tilopay === '') {
									emitErrorValidations = true
								} else {
									emitErrorValidations = false;
								}
							}
							if (emitErrorValidations) {
								return {
									type: emitResponse.responseTypes.ERROR,
									message: __('Check credit or debit card details', 'tilopay')
								}
							}
						}

						if (paymentData.tpay_env !== 'PROD' && checkoutData.have_subscription === true) {
							setPaymentError(__('Subscriptions payment is not allowed in test environment.', 'tilopay'));
						} else {
							setPaymentError(undefined);
						}

						if (typeof paymentData.tlpy_payment_method === 'undefined') {
							return {
								type: emitResponse.responseTypes.ERROR,
								message: 'Not payment method selected.',
							};
						}

					}

					return {
						type: emitResponse.responseTypes.SUCCESS,
						meta: {
							paymentMethodData: paymentData,
						},
					};

				}

				return {
					type: emitResponse.responseTypes.ERROR,
					message: 'There was an error with Tilopay.',
				};
			});

			// Unsubscribes when this component is unmounted.
			return () => {
				unsubscribe();
			};
		}, [
			emitResponse.responseTypes.ERROR,
			emitResponse.responseTypes.SUCCESS,
			onPaymentSetup,
			paymentData
		]);


		// On checkout fail
		useEffect(() => {
			//https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/internal-developers/block-client-apis/checkout/checkout-flow-and-events.md
			;
			const unsubscribe = onCheckoutFail(async () => {

				// Handle payment error btn
				if (typeof selectedPaymentMethod !== 'undefined') {
					placeHolderBtnWooCommerceOverride();
				}

				if (paymentData.pay_sinpemovil_tilopay === true) {
					return {
						type: emitResponse.responseTypes.FAIL,
						message: 'Sinpe movil payment is not supported by Tilopay.',
					};
				}
				return {
					type: emitResponse.responseTypes.FAIL,
					message: 'There was an error with Tilopay.',
				};
			});

			// Unsubscribes when this component is unmounted.
			return () => {
				unsubscribe();
			};
		}, [
			emitResponse.responseTypes.FAIL,
			onCheckoutFail
		]);


		//Error payment validations
		useEffect(() => {
			if (wc_payment_result) {
				// Obtener el estado del proceso de pago
				const { paymentStatus, message, redirectUrl, paymentDetails } = wc_payment_result;
				if (paymentDetails) {

					const { result, messages } = paymentDetails;
					let order_id = paymentDetails?.order_id ?? undefined;
					if (order_id != undefined && order_id != null) {
						setOrderId(order_id);
						setTimeout(() => {
							setActiveModal(true);
						}, 1000);
					}

					if (result === "failure" && messages) {
						setPaymentError(messages)
						// Handle payment error btn
						if (typeof selectedPaymentMethod !== 'undefined') {
							placeHolderBtnWooCommerceOverride();
						}
					}
				}
			}

		}, [wc_payment_result]);

		// get order id for sinpe movil
		useEffect(() => {
			if (orderId) {
				initSdkSettings.orderNumber = orderId;
				initSdkSettings.have_order_id = true;

				setTimeout(async () => {
					await getPaymentMethodOptions();
				}, 100);
			}

		}, [orderId]);

		return (
			<>

				{ paymentError &&
					<WcNoticeBlock
						type="is-error"
						message={ paymentError }
					/>
				}

				<div className={ "wc-block-components-radio-control-accordion-content" }>

					<div id="loaderTpay" payformtilopay="" style={ { display: loaderTpay } }>
						<div className={ "spinnerTypayInit" }></div>
					</div>


					{ typeof sdkInitResponse?.environment != "undefined" && sdkInitResponse?.environment !== "PROD" &&
						<WcNoticeBlock
							type="is-error"
							message={ __('TEST MODE ENABLED. In test mode, you can use the card numbers listed in', 'tilopay') }
							link={ {
								text: 'Admin Tilopay',
								url: 'http://admin.tilopay.com/',
							} }
						/>

					}

					{ !paymentData.tpay_can_make_payment ?
						(<WcNoticeBlock
							type="is-error"
							message={ __("There seems to be an issue with this website's integration with Tilopay, please inform the seller to complete your purchase.", 'tilopay') + 'processor : 1' }
						/>)
						: (
							<>
								<div className={ "payFormTilopay" }>
									{ typeof sdkInitResponse == "undefined" && loaderTpay == 'none' &&
										<>
											<p>{ __('Oops! Something went wrong.', 'tilopay') }</p>
											<button type="button" id="call-tilopay" className={ "button alt process-sinpemovil-tilopay" }
												onClick={ getPaymentMethodOptions }>
												{ __('Try again', 'tilopay') }
											</button>
										</>
									}
									{ typeof sdkInitResponse != "undefined" &&
										<div className={ "form-row form-row-wide" }>
											<PaymentMethodOptionTilopay
												initSdkSettings={ initSdkSettings }
												sdkInitResponse={ sdkInitResponse }
												selectedPaymentMethod={ selectedPaymentMethod }
												setSelectedPaymentMethod={ setSelectedPaymentMethod }
												paymentData={ paymentData }
												setPaymentData={ setPaymentData }
												checkoutData={ checkoutData }
												setPaymentError={ setPaymentError }
												paymentError={ paymentError }
												activeModal={ activeModal }
												setActiveModal={ setActiveModal }
											/>
										</div>
									}

									{ selectedPaymentMethod?.type === "card" &&
										<AddNewCardFormTilopay
											paymentData={ paymentData }
											setPaymentData={ setPaymentData }
											checkoutData={ checkoutData }
											setPaymentError={ setPaymentError }
											paymentError={ paymentError }
											sdkInitResponse={ sdkInitResponse }
										/>
									}

									{ selectedPaymentMethod?.type === "sinpemovil" && (
										have_subscription ? (
											// No permitido
											<div className={ "tpayEnvAlert" } style={ { marginTop: '15px' } }>
												<span id="environment">
													{ __('You cannot pay subscriptions with SINPE Móvil, please pay with a credit or debit card', 'tilopay') }
												</span>
											</div>
										) : (
											orderId !== undefined ? (
												<SinpeMovilTilopay
													selectedPaymentMethod={ selectedPaymentMethod }
													setSelectedPaymentMethod={ setSelectedPaymentMethod }
													initSdkSettings={ initSdkSettings }
													checkoutData={ checkoutData }
													paymentData={ paymentData }
													sdkInitResponse={ sdkInitResponse }
													activeModal={ activeModal }
													setActiveModal={ setActiveModal }
												/>
											) : (
												<WcNoticeBlock
													type="is-success"
													message={ __('The payment instructions with SINPE Móvil will be shown on the next screen.', 'tilopay') }
												/>
											)
										)
									) }

									{ selectedPaymentMethod?.type === "yappy" && (
										have_subscription ? (
											// No permitido
											<div className={ "tpayEnvAlert" } style={ { marginTop: '15px' } }>
												<span id="environment">
													{ __('You cannot pay subscriptions with YAPPY, please pay with a credit or debit card', 'tilopay') }
												</span>
											</div>
										) : (
											<PaymentYappyTilopay
												paymentData={ paymentData }
												setPaymentData={ setPaymentData }
												checkoutData={ checkoutData }
												setPaymentError={ setPaymentError }
												paymentError={ paymentError }
												sdkInitResponse={ sdkInitResponse }
											/>
										)
									) }

								</div>
							</>
						)
					}

				</div>

				{/* Just to use tilopay sdk thats way we have hidden and no be use for other things */ }
				{ removeHideInput === false && <SdkHtmlForm /> }
				{/* Just to use tilopay sdk thats way we have hidden and no be use for other things */ }
			</>
		)
	}
};


/**
 * Label component
 *
 * @param {*} props Props from payment API.
			*/
const Label = (props) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ label } />;
};

/**
 * Tilopay payment method config object.
 */
const Tilopay = {
	paymentMethodId: settings.id,
	name: settings.id,
	label: <Label />,
	content: <TilopayComponent />,
	edit: <TilopayComponent />,
	canMakePayment: () => true,
	ariaLabel: label,
	//placeOrderButtonLabel: __('Pay with Tilopay', 'tilopay'),
	supports: {
		features: settings.supports,
		//showSavedCards: true,
		//showSaveOption: true,
	},
	//savedTokenComponent: <SavedTokenComponent />,
};

registerPaymentMethod(Tilopay);
