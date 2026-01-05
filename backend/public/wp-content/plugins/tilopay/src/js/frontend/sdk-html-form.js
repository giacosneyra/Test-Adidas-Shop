/**
 * SdkHtmlForm function that returns a JSX element for the Tilopay payment form.
 * All are hidden just to use SKD js on the frontend.
 *
 * @return {JSX.Element} The JSX element representing the Tilopay payment form.
 */
export const SdkHtmlForm = () => {
    return (
        <>
            <div id="responseTilopay"></div>
            <div className={ "payFormTilopay" } style={ { display: 'none' } }>
                <div className={ "form-row form-row-wide" } id="selectCard" style={ { display: 'none' } }>
                    <label>Tarjetas guardadas</label>
                    <select name="cards" id="cards">
                        <option value={ "" } desabled={ "true" }>Seleccionar tarjeta</option>
                    </select>
                </div>

                <div className={ "form-row form-row-wide" } id="selectMethods" style={ { display: 'none' } }>
                    <label>Methdos</label>
                    <select name="tlpy_payment_method" id="tlpy_payment_method">
                        <option value={ "" } desabled={ "true" }>Seleccionar método</option>
                    </select>
                </div>

                <input
                    type={ "hidden" }
                    id={ "tlpy_cc_number" }
                    name={ "tlpy_cc_number" }
                    inputMode={ "numeric" }
                    autoComplete={ "off" }
                    autoCorrect={ "no" }
                    autoCapitalize={ "no" }
                    spellCheck={ "no" }
                    value={ "" }
                />
                <input
                    type={ "hidden" }
                    id={ "tlpy_cc_expiration_date" }
                    name={ "tlpy_cc_expiration_date" }
                    inputMode="numeric"
                    autoComplete={ "off" }
                    autoCorrect={ "no" }
                    autoCapitalize={ "no" }
                    spellCheck={ "no" }
                    value={ "" }
                />
                <input
                    id={ "tlpy_cvv" }
                    name={ "tlpy_cvv" }
                    inputMode={ "numeric" }
                    autoComplete={ "off" }
                    autoCorrect={ "no" }
                    autoCapitalize={ "no" }
                    spellCheck={ "no" }
                    type={ "hidden" }
                    maxLength={ "4" }
                    value={ "" }
                />
            </div>
        </>
    );
}
