import { useState, useEffect } from 'react';
import { CheckboxControl } from '@woocommerce/blocks-checkout';
import { useDispatch, dispatch, select, useSelect } from '@wordpress/data';
import { sprintf, __ } from '@wordpress/i18n';
import { MaskedInput, createDefaultMaskGenerator } from 'react-hook-mask';

export const PaymentYappyTilopay = ({ paymentData, setPaymentData, checkoutData, setPaymentError, paymentError, sdkInitResponse }) => {

    const [phoneYappyActive, setPhoneYappyActive] = useState(false);

    const { setValidationErrors, clearValidationError } = useDispatch('wc/store/validation');

    useEffect(() => {

        if (checkoutData.have_subscription === true && typeof sdkInitResponse?.environment != "undefined" && sdkInitResponse?.environment === "PROD") {
            setPaymentError(__('You cannot pay subscriptions with YAPPY, please pay with a credit or debit card', 'tilopay'));
            return;
        }

    }, [
        clearValidationError,
        setValidationErrors,
    ]);

    const maskInputHandler = (value) => {
        setPhoneYappyActive(value !== '' ? true : false)
        setPaymentData(prevData => ({
            ...prevData,
            tlpy_yappy_phone: value,
        }));
    }

    return (
        <>
            <div className={ "payFormTilopay" }>

                <div className={ "form-row form-row-wide" }>
                    <div
                        className={ `wc-block-components-text-input wc-block-components-address-form__tlpy_yappy_phone ${ phoneYappyActive ? 'is-active' : '' }` }>
                        <MaskedInput
                            maskGenerator={ createDefaultMaskGenerator("9999 9999") }
                            type="tel"
                            id="tlpy_yappy_phone"
                            name="tlpy_yappy_phone"
                            inputMode="numeric"
                            autoComplete="off"
                            autoCorrect="no"
                            autoCapitalize="no"
                            spellCheck="no"
                            value={ paymentData.tlpy_yappy_phone }
                            onChange={ maskInputHandler }
                        />
                        <label htmlFor="tlpy_yappy_phone">{ __("Yappy phone number", "tilopay")}</label>
                    </div>

                </div>
            </div>
        </>
    )
}

