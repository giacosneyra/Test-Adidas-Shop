import { useState, useEffect } from 'react';
import { sprintf, __ } from '@wordpress/i18n';

export const PaymentMethodOptionTilopay = ({ initSdkSettings, sdkInitResponse, selectedPaymentMethod, setSelectedPaymentMethod, paymentData, setPaymentData, checkoutData, setPaymentError, paymentError, activeModal, setActiveModal }) => {

    const [paymentMethod, setPaymentMethod] = useState([]);


    //sdkInitResponse.methods
    useEffect(() => {
        // Clonar el método donde apple_pay es igual a 1
        const applePayMethod = sdkInitResponse.methods.find(method => method.apple_pay === 1);

        // Si existe, clonar y modificar el nombre
        const clonedMethod = applePayMethod ? { ...applePayMethod, name: 'Apple Pay', id: applePayMethod.id + '_applepay', type: 'applepay' } : null;

        // if have Apple Pay selected
        if (clonedMethod) {
            setPaymentMethodDetails(clonedMethod);
        }
        // Agregar el método clonado al array si es que existe
        const updatedMethods = clonedMethod ? [...sdkInitResponse.methods, clonedMethod] : sdkInitResponse.methods;

        if (updatedMethods.length > 0) {
            if (updatedMethods.length == 1) {
                setPaymentMethodDetails(updatedMethods[0]);
            }
            setPaymentMethod(updatedMethods)
        }

    }, [sdkInitResponse]);


    const renderPaymentMethodOptions = (methods) => {

        return methods.map((method, index) => {

            let valideDisabled = (checkoutData.have_subscription && (method.type === 'sinpemovil' || method.type === 'applepay')) ? 'disabled' : null;
            return (
                <option key={ index } value={ method.id } disabled={ valideDisabled } >
                    { method.name } { (checkoutData.have_subscription && (method.type === 'sinpemovil' || method.type === 'applepay')) ? __('Disabled for subscriptions', 'tilopay') : '' }
                </option>
            )
        });
    }

    const handleOnChangePaymentMethod = ({ target }) => {
        const { value } = target;
        const method = paymentMethod.find(item => item.id === value);
        setPaymentMethodDetails(method);
    }

    const setPaymentMethodDetails = (method) => {
        if (method.type === "sinpemovil") {
            setPaymentError(undefined);
            if (initSdkSettings.have_order_id) {
                setActiveModal(true);
            }

        }

        const splitMethod = method.id.split(":");
        const sinpeMovilNumber = (splitMethod.length > 1 && splitMethod[1] == '4')
            ? splitMethod[2]
            : '';

        const isYappyPayment = (splitMethod.length > 1 && splitMethod[1] == '18');

        setPaymentData(prevData => ({
            ...prevData,
            tlpy_payment_method: method.id,
            pay_sinpemovil_tilopay: (method.type === "sinpemovil" && sdkInitResponse.environment == 'PROD' && splitMethod[1] == '4') ? true : false,
            tlpy_sinpemovil_number: sinpeMovilNumber,
            tpay_env: sdkInitResponse.environment,
            tpay_can_make_payment: splitMethod[1] == '1' ? false : true,
            tlpy_is_yappy_payment: isYappyPayment
        }));

        setSelectedPaymentMethod(method);
    }

    return (
        <>
            { paymentMethod.length > 0 ? (
                <>
                    <div style={ paymentMethod.length == 1 ? { display: 'none' } : { display: 'block' } }>
                        { selectedPaymentMethod !== undefined && selectedPaymentMethod.type === 'applepay' &&
                            <input type="hidden" id="tlpy_apple_pay_cancel" value={ '' } /> }

                        <label htmlFor="tlpy_payment_method" id="methodLabel">{ __('Payment methods', 'tilopay') }</label>
                        <select
                            name="tlpy_payment_method"
                            id="tlpy_payment_method"
                            className={ "select wc-credit-card-form-card-select" }
                            onChange={ handleOnChangePaymentMethod }
                            style={ { height: 50 } }
                            value={ selectedPaymentMethod !== undefined ? selectedPaymentMethod.id : '' }
                        >
                            <option value="" desabled={ "true" }>
                                { __('Select payment method', 'tilopay') }
                            </option>
                            { renderPaymentMethodOptions(paymentMethod) }
                        </select>
                    </div>
                </>
            ) : (
                paymentMethod.length === 0 && (
                    <div className={ "woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout" } id="tpay-sdk-error-div" style={ { opacity: 1 } }>
                        <ul className={ "woocommerce-error" } role="alert" id="tpay-sdk-error">
                            <li className={ "error-sdk-li" }>{ __("There seems to be an issue with this website's integration with Tilopay, please inform the seller to complete your purchase.", 'tilopay') }</li>
                        </ul>
                    </div>
                )
            ) }

        </>
    )
}

