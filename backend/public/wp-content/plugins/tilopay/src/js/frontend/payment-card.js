import { useState, useEffect } from 'react';
import { CheckboxControl } from '@woocommerce/blocks-checkout';
import { useDispatch, dispatch, select, useSelect } from '@wordpress/data';
import { sprintf, __ } from '@wordpress/i18n';
import { MaskedInput, createDefaultMaskGenerator } from 'react-hook-mask';

export const AddNewCardFormTilopay = ({ paymentData, setPaymentData, checkoutData, setPaymentError, paymentError, sdkInitResponse }) => {

    const [isCardNumberActive, setCardNumberActive] = useState(false);
    const [isExpirationDateActive, setExpirationDateActive] = useState(false);
    const [isCvvActive, setCvvActive] = useState(false);
    const [checked, setChecked] = useState(false);

    const { setValidationErrors, clearValidationError } = useDispatch('wc/store/validation');

    const [cardList, setCardList] = useState([]);
    const [cardIcon, setCardIcon] = useState('flat_icon_tilopay.png');
    const [inputError, setInputError] = useState([]);
    const [selectedCard, setSelectedCard] = useState(cardList.length === 0 ? "" : "newCard");

    //sdkInitResponse.methods
    useEffect(() => {
        if (sdkInitResponse.methods.length > 0) {
            setCardList(sdkInitResponse.cards)
        }

    }, [sdkInitResponse]);

    useEffect(() => {

        if (!checked && checkoutData.have_subscription === true && paymentData.cards === 'newCard' && typeof sdkInitResponse?.environment != "undefined" && sdkInitResponse?.environment === "PROD") {
            setPaymentError(__('For subscriptions you must click on the checkbox save card', 'tilopay'));
            setInputError(prevInputError => [...prevInputError, 'checkbox-control-tpay']);
            setChecked(true);
            return;
        } else {
            setInputError(prevInputError => prevInputError.filter(item => item !== 'checkbox-control-tpay'));
            setPaymentError(undefined);
        }
        //clearValidationError('tpay-subscription-save-card');
        setPaymentError(undefined);
        setPaymentData(prevData => ({
            ...prevData,
            tpay_save_card: checked ? 'on' : 'off'
        }));

    }, [
        clearValidationError,
        setValidationErrors,
        checked
    ]);

    useEffect(() => {

        if (paymentData.tlpy_cvv !== '000' && paymentData.tlpy_cvv !== '') {
            if (shouldCallHandleCipherData()) {
                handleCipherData();
            } else {
                // Not need to call handleCipherData
                setPaymentData(prevData => ({
                    ...prevData,
                    token_hash_card_tilopay: paymentData.tlpy_cc_number ?? 'NA',
                    token_hash_code_tilopay: paymentData.tlpy_cvv ?? 'NA'
                }));

            }
        }

    }, [paymentData.tlpy_cc_number, paymentData.tlpy_cvv, paymentData.tlpy_cc_expiration_date]);

    const shouldCallHandleCipherData = () => {
        if (paymentData.tpay_env === 'PROD' && paymentData.pay_sinpemovil_tilopay === false) {

            if (paymentData.card_type_tilopay === 'amex') {
                if (paymentData.cards !== 'newCard' && paymentData.cards !== '') {
                    return (
                        paymentData.tlpy_cvv != null &&
                        paymentData.tlpy_cvv.length === 4
                    );
                }

                return (
                    paymentData.tlpy_cc_number != null &&
                    paymentData.tlpy_cc_number.length >= 15 &&
                    paymentData.tlpy_cc_expiration_date != null &&
                    paymentData.tlpy_cc_expiration_date.length >= 4 &&
                    paymentData.tlpy_cvv != null &&
                    paymentData.tlpy_cvv.length === 4
                );
            } else {
                if (paymentData.cards !== 'newCard' && paymentData.cards !== '') {
                    return (
                        paymentData.tlpy_cvv != null &&
                        paymentData.tlpy_cvv.length === 3
                    );

                }

                return (
                    paymentData.tlpy_cc_number != null &&
                    paymentData.tlpy_cc_number.length >= 15 &&
                    paymentData.tlpy_cc_expiration_date != null &&
                    paymentData.tlpy_cc_expiration_date.length >= 4 &&
                    paymentData.tlpy_cvv != null &&
                    paymentData.tlpy_cvv.length === 3
                );
            }

        }
        return false;
    };

    const handleCipherData = async () => {
        const { cipherCardNumber, cipherCvv } = await makeCipherData(paymentData);
        console.log({ cipherCardNumber, cipherCvv, paymentData });

        if (cipherCardNumber !== null && cipherCvv !== null) {

            setPaymentData(prevData => ({
                ...prevData,
                token_hash_card_tilopay: cipherCardNumber,
                token_hash_code_tilopay: cipherCvv
            }));

        } else if (cipherCvv !== null && cipherCardNumber === undefined) {
            paymentData.token_hash_code_tilopay = cipherCvv;
            setPaymentData(prevData => ({
                ...prevData,
                token_hash_code_tilopay: cipherCvv
            }));
        }

    };

    const cardMaskInputHandler = (value) => {
        setCardNumberActive(value !== '' ? true : false)
        let cardBrand = payform.parseCardType(value)
        setPaymentData(prevData => ({
            ...prevData,
            tlpy_cc_number: value,
            card_type_tilopay: cardBrand
        }));
        switch (cardBrand) {
            case "visa":
                setCardIcon('visa.svg');
                break;
            case "mastercard":
                setCardIcon('mastercard.svg');
                break;
            case "amex":
                setCardIcon('american_express.svg');
                break;
            default:
                setCardIcon('flat_icon_tilopay.png');
                break;
        }
    }

    const expiryDateInputHandler = (value) => {
        setExpirationDateActive(value !== '' ? true : false)
        setPaymentData(prevData => ({
            ...prevData,
            tlpy_cc_expiration_date: value,
        }));
    }

    const expiryDateOnBlurHandler = () => {
        const value = paymentData.tlpy_cc_expiration_date
        const month = value.substring(0, 2);
        const year = value.substring(2);
        if (value.length > 2) {
            const today = new Date()
            const currentYear = today.getFullYear();
            const currentMonth = today.getMonth() + 1;
            const currentYearValid = currentYear % 100;

            let monthValid = !isNaN(month) && month >= 1 && month <= 12;
            if (year <= currentYearValid && year !== '') {
                monthValid = month > currentMonth;
            }

            const yearValid = !isNaN(year) && year >= currentYearValid;
            if (!monthValid || !yearValid) {
                setPaymentError(__('Please enter a valid expiration date.', 'tilopay'));
                setInputError(prevInputError => [...prevInputError, 'tlpy_cc_expiration_date']);
            } else {
                setInputError(prevInputError => prevInputError.filter(item => item !== 'tlpy_cc_expiration_date'));
                setPaymentError(undefined);
            }
        }
    }

    const cvvInputHandler = (value) => {
        setCvvActive(value !== '' ? true : false)
        setPaymentData(prevData => ({
            ...prevData,
            tlpy_cvv: value,
        }));

    }

    const onBlurHandler = () => {

        if (paymentData.card_type_tilopay === 'amex' && paymentData.tlpy_cvv.length !== 4) {
            setPaymentError(__('Please enter a CVV valid for AMEX cards.', 'tilopay'));
            setInputError(prevInputError => [...prevInputError, 'tlpy_cvv']);
        } else if (paymentData.card_type_tilopay !== 'amex' && paymentData.tlpy_cvv.length !== 3) {
            setPaymentError(__('Please enter a valid CVV for', 'tilopay') + ' ' + paymentData.card_type_tilopay);
            setInputError(prevInputError => [...prevInputError, 'tlpy_cvv']);
        } else {
            setInputError(prevInputError => prevInputError.filter(item => item !== 'tlpy_cvv'));
            setPaymentError(undefined);
        }
    }

    const selectCardListHandler = ({ target }) => {
        const { value } = target;
        let cardBrand = '';
        if (value !== 'newCard' || value !== '') {
            const firstFourDigits = value.substring(0, 4);
            cardBrand = payform.parseCardType(value);
            setSelectedCard(value);
        }

        setPaymentData(prevData => ({
            ...prevData,
            cards: value,
            card_type_tilopay: cardBrand,
            tlpy_cvv: '',
        }));

    }

    return (
        <>
            <div className={ "payFormTilopay" }>

                { cardList.length > 0 &&
                    <div className={ "form-row form-row-wide" } style={ { marginTop: '10px' } }>
                        <label htmlFor="cards">{ __("Saved cards", "tilopay")}</label>
                        <select name="cards"
                            id="cards"
                            className={ "select wc-credit-card-form-card-select" }
                            onChange={ selectCardListHandler }
                            style={ { height: 50 } }
                            value={ selectedCard }
                        >
                            <option value="" desabled={ "true" }>{ __("Select card", "tilopay")}</option>
                            { cardList.map((card, index) => (
                                <option key={ index } value={ card.id.split(":")[0] } >
                                    { card.name }
                                </option>
                            ))
                            }
                            <option value="newCard">{ __('Pay with another card', 'tilopay') }</option>
                        </select>
                    </div>
                }


                <div className={ "form-row form-row-wide" }>
                    { paymentData.cards === 'newCard' &&
                        <div
                            className={ `wc-block-components-text-input wc-block-components-address-form__tlpy_cc_number ${ isCardNumberActive ? 'is-active' : '' }` }>
                            <MaskedInput
                                maskGenerator={ createDefaultMaskGenerator("9999 9999 9999 9999") }
                                type="tel"
                                id="tlpy_cc_number"
                                name="tlpy_cc_number"
                                inputMode="numeric"
                                autoComplete="off"
                                autoCorrect="no"
                                autoCapitalize="no"
                                spellCheck="no"
                                value={ paymentData.tlpy_cc_number }
                                onChange={ cardMaskInputHandler }
                                style={ {
                                    backgroundImage: "url(" + checkoutData.tpayPluginUrl + "assets/images/" + cardIcon + ")",
                                    backgroundRepeat: "no-repeat",
                                    backgroundPosition: "right 0.6180469716em center",
                                    backgroundSize: "31px 20px"
                                } }
                            />
                            <label htmlFor="tlpy_cc_number">{ __("Card number", "tilopay")}</label>
                        </div>
                    }

                    <div className={ `'wc-block-components-address-form__expiration-cvv'` } style={ { display: 'flex' } }>
                        { paymentData.cards === 'newCard' &&
                            <div className={ `wc-block-components-text-input wc-block-components-address-form__tlpy_cc_expiration_date ${ isExpirationDateActive ? 'is-active' : '' } ${ inputError.includes('tlpy_cc_expiration_date') ? 'has-error' : '' }` } style={ { flex: 1, marginRight: '10px' } }>
                                <MaskedInput
                                    maskGenerator={ createDefaultMaskGenerator("99 / 99") }
                                    type="tel"
                                    id="tlpy_cc_expiration_date"
                                    name="tlpy_cc_expiration_date"
                                    inputMode="numeric"
                                    autoComplete="off"
                                    autoCorrect="no"
                                    autoCapitalize="no"
                                    spellCheck="no"
                                    value={ paymentData.tlpy_cc_expiration_date }
                                    onChange={ expiryDateInputHandler }
                                    onBlur={ expiryDateOnBlurHandler }
                                />
                                <label htmlFor="tlpy_cc_expiration_date">{ __("Expiry date", "tilopay")}</label>
                            </div>
                        }

                        <div className={ `wc-block-components-text-input wc-block-components-address-form__tlpy_cvv ${ isCvvActive ? 'is-active' : '' } ${ inputError.includes('tlpy_cvv') ? 'has-error' : '' }` } style={ { flex: 1 } }>
                            <MaskedInput
                                maskGenerator={ createDefaultMaskGenerator("9999") }
                                id="tlpy_cvv"
                                name="tlpy_cvv"
                                type="tel"
                                autoComplete="off"
                                autoCorrect="no"
                                autoCapitalize="no"
                                spellCheck="no"
                                maxLength={ paymentData.card_type_tilopay === 'amex' ? 4 : 3 }
                                value={ paymentData.tlpy_cvv }
                                onChange={ cvvInputHandler }
                                onBlur={ onBlurHandler }
                            />
                            <label htmlFor="tlpy_cvv">CVV</label>
                        </div>
                    </div>

                    { paymentData.cards === 'newCard' && paymentData.tpay_env === "PROD" &&
                        <CheckboxControl
                            id="checkbox-control-tpay"
                            className={ inputError.includes('checkbox-control-tpay') ? 'has-error' : '' }
                            checked={ checked }
                            onChange={ setChecked }
                            desabled={ checkoutData.have_subscription && checked ? 'disabled' : null }
                        >
                            { __('Save card', 'tilopay') }
                        </CheckboxControl>
                    }
                </div>
            </div>
        </>
    )
}

