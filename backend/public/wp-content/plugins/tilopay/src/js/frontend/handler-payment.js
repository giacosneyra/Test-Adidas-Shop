import React, { useEffect } from 'react';

/**
 * HandlerPayment function handles payment processing and integration with Apple Pay.
 *
 * @param {object} initSdkSettings - The initial SDK settings.
 * @param {object} sdkInitResponse - The SDK initialization response.
 * @param {function} setPaymentData - Function to set payment data.
 * @param {function} setPaymentError - Function to set payment error.
 * @param {object} selectedPaymentMethod - The selected payment method object.
 * @param {function} setApplePayProcessed - Function to set whether Apple Pay processing is completed.
 *
 * @returns {object} An object containing placeHolderBtnWooCommerceOverride and applePayIntegrationTilopay functions.
 */
export const HandlerPayment = (initSdkSettings, sdkInitResponse, setPaymentData, setPaymentError, selectedPaymentMethod, setApplePayProcessed) => {

    // Defines CSS styles as a string
    const stylesApplePayButton = `
.apple-pay-button {
    display: inline-block;
    width: 100%;
    height: 53px;
    cursor: pointer;
    margin-top: 15px;
    -webkit-appearance: -apple-pay-button;
    --apple-pay-button-width: 100%;
    --apple-pay-button-height: 50px;
    --apple-pay-button-border-radius: 0px;
    --apple-pay-button-padding: 0px 0px;
    --apple-pay-button-box-sizing: content-box;
}

.buttonApplePayProgress {
    background: #4C5562;
    border: 1px solid #4C5562;
    color: #fff;
    border-radius: 8px;
    padding: 10px 105px;
    width: 100%;
    position: relative;
    pointer-events: none;
    display: inline-block;
    margin-top: 15px;
    -webkit-appearance: -apple-pay-button;
    --apple-pay-button-width: 100%;
    --apple-pay-button-height: 50px;
    --apple-pay-button-border-radius: 0px;
    --apple-pay-button-padding: 0px 0px;
    --apple-pay-button-box-sizing: content-box;
}
`;

    const placeHolderBtnWooCommerceOverride = async () => {
        // Check if the element exists
        var styleElementApplePayButtonExist = document.getElementById("apple_pay_button_style_tpay");
        if (styleElementApplePayButtonExist) {
            // Removes the style element if it already exists
            styleElementApplePayButtonExist.remove();

        }

        const placeOrderButton = document.querySelector('.wc-block-components-checkout-place-order-button');
        const wooButtonText = document.querySelector('.wc-block-components-button__text');
        if (selectedPaymentMethod.apple_pay === 1 && selectedPaymentMethod.type === 'applepay') {

            // Create the style element
            var styleElementApplePayButton = document.createElement("style");
            styleElementApplePayButton.id = "apple_pay_button_style_tpay";
            // Add the styles to the style element
            styleElementApplePayButton.appendChild(document.createTextNode(stylesApplePayButton));

            // Inserta el elemento de estilo en la cabecera del documento
            document.head.appendChild(styleElementApplePayButton);

            if (placeOrderButton) {
                placeOrderButton.classList.add('apple-pay-button');
                placeOrderButton.style.height = '53px';

                // Adding the click event for Apple Pay
                //placeOrderButton.addEventListener('click', handleApplePayClick);
            }

            if (wooButtonText) {
                wooButtonText.style.display = 'none';
            }
        } else {
            if (wooButtonText) {
                wooButtonText.style.display = 'block';
            }

            if (placeOrderButton) {
                wooButtonText.classList.remove('apple-pay-button');
                // Remueve el evento de clic para Apple Pay
                //placeOrderButton.removeEventListener('click', handleApplePayClick);
            }
        }
    }


    const applePayIntegrationTilopay = async () => {

        if (sdkInitResponse?.applePaySupported && selectedPaymentMethod.apple_pay === 1 && selectedPaymentMethod.type === 'applepay') {
            try {
                // Process the Apple Pay payment
                const responseSDK = await processSDKWithApplePay();
                return (responseSDK) ? JSON.stringify(responseSDK) : responseSDK;

            } catch (error) {
                console.error('Error procesando el pago con Apple Pay:', error);
                setPaymentError(error);
                setApplePayProcessed(false);
            }
        }
        return false;
    }

    return {
        placeHolderBtnWooCommerceOverride,
        applePayIntegrationTilopay
    }
}
