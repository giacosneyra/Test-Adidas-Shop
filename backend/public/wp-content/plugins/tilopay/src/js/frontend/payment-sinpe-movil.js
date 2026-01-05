import React, { useState, useEffect } from 'react';
import { sprintf, __ } from '@wordpress/i18n';
//Import FE js to use Tilopay SDK
import '../../../assets/js/block-tilopay-checkout.js';

export const SinpeMovilTilopay = ({ selectedPaymentMethod, setSelectedPaymentMethod, initSdkSettings, checkoutData, paymentData, sdkInitResponse, activeModal, setActiveModal }) => {

    const { sinpemovil } = sdkInitResponse;

    useEffect(() => {
        if (activeModal) {
            setTimeout(async () => {
                await sinpeMovilEventTrigger();
            }, 1000);
        }
    }, [activeModal]);

    const handlerOnClick = () => {
        setActiveModal(false);
        setSelectedPaymentMethod(undefined);
    }

    return (
        <>
            <div id="tilopay-m1" className={ `tilopay-modal-container ${ activeModal ? 'active' : '' }` }>
                <div className={ "tilopay-overlay" } data-modal="close"></div>
                <div className={ "tilopay-modal" }>
                    <h3>{ __('Pay with SINPE Móvil', 'tilopay') }</h3>
                    <p>{ __('To make the payment with SINPE Móvil, you must make sure to make the payment in the following way:', 'tilopay') } <br />
                        { __('Telephone:', 'tilopay') } <strong id="tilopay-sinpemovil-number">{ paymentData.tlpy_sinpemovil_number }</strong><br />
                        { __('Exact amount:', 'tilopay') } <strong> { checkoutData.currency } </strong> <strong id="tilopay-sinpemovil-amount">{ sinpemovil.amount }</strong><br />
                        { __('Specify in the description:', 'tilopay') } <strong id="tilopay-sinpemovil-code">{ sinpemovil.code }</strong><br />

                    </p>

                    <div className={ "tilopay-btn-group" }>
                        <button type="button" className={ "button btn-tilopay-close-modal" }
                            onClick={ handlerOnClick }
                            style={ { marginRight: '10px' } } >
                            { __('Cancel', 'tilopay') }
                        </button>
                        <button type="button" id="process-tilopay" className={ "button alt process-sinpemovil-tilopay loading" } desabled={ "true" }>
                            { __('Waiting payment', 'tilopay') }
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
};

