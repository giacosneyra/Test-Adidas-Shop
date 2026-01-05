/**
 *
 * This file is implemented
 * From Tilopay plugin V 2.0.3
 * more info tilopay.com
 * New Version 3.0.9
 *
 */
jQuery(document).ready(function ($) {

  let searchParams = new URLSearchParams(window.location.search);
  let returnData = searchParams.get("returnData");
  let description = searchParams.get("description");
  let message_error = searchParams.get("message_error");
  if (description) {
    addTilopayErrorMessage(description);
  } else if (message_error) {
    addTilopayErrorMessage(message_error);
  }

  if (returnData == 'tilopay') {
    var cleanUrl = window.location.origin + window.location.pathname;
    history.replaceState(null, null, cleanUrl);

    showTilopaySpinner();
    setTimeout(() => {
      var radioButton = document.querySelector('#radio-control-wc-payment-method-options-tilopay');
      radioButton.click();
      //Hide loader
      hideTilopaySpinner();
    }, 1000);
  }

}); //.end ready

async function set_card_icon() {
  return await Tilopay.getCardType();
}

function addTilopayErrorMessage(description) {
  // Eliminar los parámetros de la URL
  // Crear una nueva URL sin los parámetros de búsqueda
  let newURL = window.location.origin + window.location.pathname;

  // Usar history.replaceState para actualizar la URL sin recargar la página
  window.history.replaceState({}, document.title, newURL);

  var alreadyDiv = document.getElementById("tilopay_response_error_div");
  if (!alreadyDiv) {
    var parentDiv = document.querySelector('div[data-block-name="woocommerce/checkout"]');
    var errorMessageHTML = `
  <div class="wc-block-components-notices wc-block-components-notices-tilopay" id="tilopay_response_error_div">
      <div class="wc-block-store-notice wc-block-components-notice-banner is-error is-dismissible">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
              <path d="M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z"></path>
          </svg>
          <div class="wc-block-components-notice-banner__content">
              <div id="tilopay_response_error">Error: ${ description }</div>
          </div>
          <button type="button" class="components-button wc-block-components-button wp-element-button wc-block-components-notice-banner__dismiss contained has-text has-icon" onClick="closeTilopayErrorMessage(this)">
              <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                  <path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path>
              </svg>
              <span class="wc-block-components-button__text"></span>
          </button>
      </div>
  </div>
  `;

    // Insertar el HTML del mensaje de error como el primer hijo del div padre
    parentDiv.insertAdjacentHTML('afterbegin', errorMessageHTML);
  }
}

function closeTilopayErrorMessage(button) {
  if (button) {
    var noticeBanner = button.closest('.wc-block-components-notices-tilopay');
    if (noticeBanner) {
      noticeBanner.style.display = 'none';
    }
  }
  // var existDiv = document.getElementById("tilopay_response_error_div");
  // if (existDiv) {
  //   existDiv.parentNode.removeChild(existDiv);
  // }
}

function onchange_select_card() {

  const cardSelected = document.getElementById('cards')?.value;
  if (typeof cardSelected !== "undefined" && cardSelected !== "") {
    if (cardSelected === "newCard" || cardSelected === "0") {
      document.getElementById('divCardNumber').style.display = "block";
      document.getElementById('divCardDate').style.display = "block";
      document.getElementById('divCardCvc').style.display = "block";
      document.getElementById('divCardCvc').classList.remove("form-row-first");
      document.getElementById('divCardCvc').classList.add("form-row-last");
      if (document.getElementById('tpay_env').value === "PROD") {
        document.getElementById('divSaveCard').style.display = "block";
        if (tilopayConfig.haveSubscription === '1') {
          document.getElementById("tpay_save_card").checked = true;
          document.getElementById("tpay_save_card").disabled = true;
        }

      }
    } else {
      document.getElementById('divCardNumber').style.display = "none";
      document.getElementById('divCardDate').style.display = "none";
      document.getElementById('divCardCvc').style.display = "block";
      document.getElementById('divCardCvc').classList.remove("form-row-last");
      document.getElementById('divCardCvc').classList.add("form-row-first");
      document.getElementById("tpay_save_card").disabled = false;
      document.getElementById("tpay_save_card").checked = false;
      if (document.getElementById('tpay_env').value === "PROD") {
        document.getElementById('divSaveCard').style.display = "none";
      }
    }
  }
}

async function initSDKTilopay(tilopayConfigBlock, initialize = undefined) {
  document.getElementById("loaderTpay").style.display = "flex";

  let payment_method_selected =
    typeof tilopayConfigBlock !== "undefined" && tilopayConfigBlock.payment_method_selected
      ? tilopayConfigBlock.payment_method_selected
      : "";

  let paymentPage = false;
  // Is not from checkout page
  if (typeof initialize === "undefined") {
    tilopayConfigBlock = tilopayConfig;
    initialize = await Tilopay.Init(tilopayConfigBlock);
    paymentPage = true;
  }

  if (initialize.methods.length > 0) {
    let paymentMethods = initialize.methods.length;
    let onlyOneMethod = 0;
    if (paymentMethods > 0) {
      //get if sinpe the first
      onlyOneMethod = initialize.methods[0].id.split(":")[1];

      //select firestone if only one
      if (paymentMethods == 1 && payment_method_selected == "") {

        if (onlyOneMethod === "4") {
          document.getElementById('selectCard').style.display = 'none';
          document.getElementById('pay_sinpemovil_tilopay').value = 1;
          document.getElementById('divTpaySinpeMovil').style.display = 'block';
          document.getElementById('divTpayCardForm').style.display = 'none';
        }

        if (paymentPage) {
          document.getElementById('tlpy_payment_method').style.display = 'none';
          document.getElementById('methodLabel').style.display = 'none';
          document.getElementById('tlpy_payment_method').value = initialize.methods[0];
        }
      } else {
        document.getElementById('tlpy_payment_method').style.display = 'block';
        document.getElementById('methodLabel').style.display = 'block';

        if (paymentPage) {
          var methodSelect = document.getElementById('tlpy_payment_method');
          //method
          initialize.methods.forEach(function (method, index) {
            var option = document.createElement('option');
            option.value = method.id;
            option.text = method.name;
            option.selected = index == 0;
            methodSelect.appendChild(option);
          });
        }
      }

      if (payment_method_selected != "") {
        document.getElementById('pay_sinpemovil_tilopay').value = 1;
        document.getElementById('selectCard').style.display = 'none';
        document.getElementById('tlpy_payment_method').value = payment_method_selected;

        setTimeout(async () => {
          var sinpemovilMethod = await Tilopay.getSinpeMovil();
          if (
            typeof sinpemovilMethod.message != "undefined" &&
            sinpemovilMethod.message == "Success" &&
            sinpemovilMethod.code != "" &&
            sinpemovilMethod.amount != "" &&
            sinpemovilMethod.number != ""
          ) {
            //init sinpeMovil
            document.getElementById('divTpayCardForm').style.display = 'none';
            //Hide loader
            hideTilopaySpinner();

            // Agregar la clase "active" al elemento con el ID "tilopay-m1"
            var element = document.getElementById('tilopay-m1');
            if (element) {
              element.classList.add('active');
            }
            //check with SDK
            var res = await Tilopay.sinpeMovil();

            // Asignar valores a los elementos con los ID "tilopay-sinpemovil-code", "tilopay-sinpemovil-amount" y "tilopay-sinpemovil-number"
            var codeElement = document.getElementById('tilopay-sinpemovil-code');
            var amountElement = document.getElementById('tilopay-sinpemovil-amount');
            var numberElement = document.getElementById('tilopay-sinpemovil-number');

            if (codeElement && amountElement && numberElement) {
              codeElement.textContent = sinpemovilMethod.code;
              amountElement.textContent = sinpemovilMethod.amount;
              numberElement.textContent = sinpemovilMethod.number;
            }

          }
        }, 100);
      }

      //Check test mode and have suscription
      if (tilopayConfigBlock.haveSubscription == '1' && initialize.environment !== "PROD") {
        document.getElementById('overlaySubscriptions').style.display = "flex";
      } else {
        document.getElementById('overlaySubscriptions').style.display = "none";
      }
    }

    //cards
    let countCard = initialize.cards.length;

    const cardsSelect = document.querySelector("#cards");
    const options = cardsSelect.querySelectorAll("option:not(:first-child)");
    options.forEach(option => option.remove());

    tilopayConfigBlock.haveCard = countCard;
    if (countCard > 0) {
      if (payment_method_selected == "" && onlyOneMethod != "4") {
        document.getElementById('selectCard').style.display = 'block';
      }

      //hide
      document.getElementById('divCardNumber').style.display = 'none';
      document.getElementById('divCardDate').style.display = 'none';
      document.getElementById('divCardCvc').style.display = 'none';
      //each card
      initialize.cards.forEach(function (card, index) {
        const option = document.createElement("option");
        option.value = card.id.split(":")[0];
        option.text = card.name;
        document.getElementById("cards").appendChild(option);
      });


      //append other card
      const newOption = document.createElement("option");
      newOption.value = "newCard";
      newOption.text = tilopayConfigBlock.newCardText;
      document.getElementById("cards").appendChild(newOption);

      document.getElementById("tpay_save_card").disabled = false;
      document.getElementById("tpay_save_card").checked = false;
      document.getElementById('divSaveCard').style.display = 'none';
    } else {
      document.getElementById('selectCard').style.display = 'none';
      if (document.getElementById('tpay_env').value === "PROD") {
        document.getElementById('divSaveCard').style.display = 'block';
        if (tilopayConfigBlock.haveSubscription === '1') {
          document.getElementById("tpay_save_card").checked = true;
          document.getElementById("tpay_save_card").disabled = true;
        }
      }
      //divCardNumber,divCardDate,divCardCvc
      //form-row form-row-first, form-row form-row-last
      document.getElementById('divCardNumber').style.display = 'block';
      document.getElementById('divCardDate').style.display = 'block';
      document.getElementById('divCardCvc').style.display = 'block';
    }
  }

  document.getElementById("loaderTpay").style.display = "none";
}

/**
 * Updates the display and values of payment method-related elements based on the selected option.
 * Only from payment page
 *
 * @param {HTMLSelectElement} selectObject - The select element containing payment method options.
 *
 * The function determines the selected payment method by parsing the value of the selectObject.
 * Based on the selected method, it adjusts the visibility of elements such as card selection,
 * SINPE Movil form, and Yappy phone div. It also updates related hidden input values to indicate
 * the currently selected payment method.
 */
function onchange_payment_method(selectObject) {
  //get sinpemovil
  let valSelected = selectObject.value;
  let paymentMethodSelected = valSelected.split(":")[1];
  document.getElementById('tlpy_is_yappy_payment').value = 0;
  document.getElementById('pay_sinpemovil_tilopay').value = 0;
  if (paymentMethodSelected == "4") {
    document.getElementById('selectCard').style.display = 'none';
    document.getElementById('pay_sinpemovil_tilopay').value = 1;
    document.getElementById('divTpaySinpeMovil').style.display = 'block';
    document.getElementById('divTpayCardForm').style.display = 'none';
    document.getElementById('yappyPhoneDiv').style.display = 'none';
  } else if (paymentMethodSelected == "18") {
    document.getElementById('selectCard').style.display = 'none';
    document.getElementById('tlpy_is_yappy_payment').value = 1;
    document.getElementById('yappyPhoneDiv').style.display = 'block';
    document.getElementById('divTpayCardForm').style.display = 'none';
  } else {
    document.getElementById('yappyPhoneDiv').style.display = 'none';
    document.getElementById('tlpy_is_yappy_payment').value = 0;
    document.getElementById('pay_sinpemovil_tilopay').value = 0;
    document.getElementById('divTpaySinpeMovil').style.display = 'none';
    document.getElementById('divTpayCardForm').style.display = 'block';
    if (tilopayConfig.haveCard > 0) {
      document.getElementById('selectCard').style.display = 'block';
    }
  }
}

function showTilopaySpinner() {
  var alreadyDiv = document.getElementById("tilopay-m1");
  if (!alreadyDiv) {
    // Crear el contenedor principal
    var modalContainer = document.createElement("div");
    modalContainer.id = "tilopay-m1";
    modalContainer.className = "tilopay-modal-container active";

    // Crear el overlay
    var overlay = document.createElement("div");
    overlay.className = "tilopay-overlay";
    overlay.setAttribute("data-modal", "close");

    var spinnerTilopay = document.createElement("div");
    spinnerTilopay.id = "spinner_Tilopay";
    spinnerTilopay.className = "spinner_Tilopay";

    // Agregar el overlay y el modal al contenedor principal
    modalContainer.appendChild(overlay);

    modalContainer.appendChild(spinnerTilopay);
    // Agregar el contenedor principal al body
    document.body.appendChild(modalContainer);

    // Load CSS
    addTilopaySpinnerCSS();
  }
}

// Hide Tpay
function hideTilopaySpinner() {
  var spinnerTilopay = document.getElementById("spinner_Tilopay");
  if (spinnerTilopay) {
    // Remove spinner from DOM if exist
    spinnerTilopay.parentNode.removeChild(spinnerTilopay);
  }
  let tilopayModal = document.getElementById("tilopay-m1");
  if (tilopayModal) {
    tilopayModal.parentNode.removeChild(tilopayModal);
  }
}

// Add CSS to documento
function addTilopaySpinnerCSS() {
  var css = ".spinner_Tilopay { \
    border: 16px solid #f3f3f3; \
    border-top: 16px solid #ff3644; \
    border-radius: 50%; \
    width: 90px; \
    height: 90px; \
    animation: spin 2s linear infinite; \
    position: fixed; \
    top: 50%; \
    left: 50%; \
    transform: translate(-50%, -50%); \
    z-index: 9999; \
  } \
  \
  @keyframes spin { \
    0% { transform: rotate(0deg); } \
    100% { transform: rotate(360deg); } \
  }";

  // Crear un elemento <style> y asignar los estilos CSS
  var styleElement = document.createElement("style");
  styleElement.type = "text/css";
  styleElement.appendChild(document.createTextNode(css));

  // Agregar el elemento <style> al <head> del documento
  document.head.appendChild(styleElement);
}

async function getSdkInit(tilopayConfig) {
  var initialize = await Tilopay.Init(tilopayConfig);
  return initialize;
}

/**
 * Process the SDK with Apple Pay by handling the Apple Pay session and cancellation.
 * Uses Promise.race to handle Apple Pay processing and cancellation listener.
 *
 * @returns {Promise} A promise that resolves with the response from Apple Pay processing or rejects with an error if the session is cancelled.
 */
async function processSDKWithApplePay() {

  const cancelPromise = new Promise((resolve, reject) => {
    const cancelInput = document.getElementById('tlpy_apple_pay_cancel');

    cancelInput.onchange = function () {
      if (cancelInput.value === 'cancelled') {
        reject(new Error('Apple Pay session cancelled'));
      }
    };
  });

  try {
    const response = await Promise.race([
      Tilopay.makeApplePaySessionForBuyer(true), // Apple Pay Process
      cancelPromise, // Cancellation Listener
    ]);
    return response;
  } catch (error) {
    return false;
  }
}

/**
 * Debugs JavaScript from WooCommerce by logging debug information.
 *
 * @param {string} debugLog - The debug information to log. Defaults to 'No hay' if not provided.
 */
async function debuggingJsFromWoocommerce(debugLog = 'No hay') {
  return await Tilopay.debuggingJs(undefined, debugLog);
}

function cleanSinpeMovilInterval() {
  if (typeof myInterval != 'undefined') {
    clearInterval(myInterval);
  }
}

async function sinpeMovilEventTrigger() {
  //myInterval is from SDk
  if (typeof myInterval != 'undefined') {
    clearInterval(myInterval);
  }
  await Tilopay.sinpeMovil()
  return true;
}

async function makeCipherData(paymentData) {
  let cipherCardNumber = undefined;
  let cipherCvv = undefined;
  // To sign data
  if (typeof cipherTextKkey != 'undefined') {

    const { tlpy_cc_number, tlpy_cc_expiration_date, tlpy_cvv } = paymentData;
    let tlpy_e_cvv = tlpy_cvv;

    let tpay_sdk_error_div = document.getElementById("tpay-sdk-error-div");
    // get element id "error-sdk-li" and make fadeout
    var errorSdkLi = document.getElementById("error-sdk-li");

    let selectCard = document.getElementById("cards");
    selectCard = (selectCard) ? selectCard.value : '';

    if (typeof tlpy_cc_number != 'undefined' && tlpy_cc_number != "" && typeof tlpy_cvv != 'undefined' && tlpy_cvv != "") {

      let [cNumberToken, cvvToken] = await Promise.all([
        TxEncrypt(cipherTextKkey, tlpy_cc_number),
        TxEncrypt(cipherTextKkey, tlpy_e_cvv)
      ]);

      //if all is ok
      if (typeof cNumberToken != "undefined" && cNumberToken != "" && typeof cvvToken != "undefined" && cvvToken != "") {
        cipherCardNumber = cNumberToken;
        cipherCvv = cvvToken;
        let hash_card = document.getElementById('token_hash_card_tilopay');
        if (hash_card) {
          hash_card.value = cNumberToken;
        }
        let hash_code = document.getElementById('token_hash_code_tilopay');
        if (hash_code) {
          hash_code.value = cvvToken;
        }

        //Clean
        if (errorSdkLi) {
          errorSdkLi.style.transition = "opacity 1s";
          errorSdkLi.style.opacity = 0;

          // Delete "error-sdk-li" after fadeout
          errorSdkLi.addEventListener("transitionend", function (event) {
            if (event.propertyName === "opacity") {
              errorSdkLi.remove();
              tpay_sdk_error_div.style.display = "none";
            }
          });
        }

      } else {
        //erro
        // Show element id "tpay-sdk-error-div"
        tpay_sdk_error_div.style.display = "block";
        let showMs = 10;
        if (errorSdkLi) {
          errorSdkLi.style.transition = "opacity 1s";
          errorSdkLi.style.opacity = 0;

          // Delete "error-sdk-li" after fadeout
          errorSdkLi.addEventListener("transitionend", function (event) {
            if (event.propertyName === "opacity") {
              errorSdkLi.remove();
            }
          });
          showMs = 1000;
        }
        setTimeout(() => {
          //Get element id "tpay-sdk-error"and add element "li" with id "error-sdk-li"
          var tpaySdkError = document.getElementById("tpay-sdk-error");
          var newLi = document.createElement("li");
          newLi.id = "error-sdk-li";
          newLi.textContent = tilopayConfig.cardError;
          tpaySdkError.appendChild(newLi);

        }, showMs);

      }
    } else if (typeof tlpy_cvv != 'undefined' && tlpy_cvv != "" && selectCard != "newCard") {
      //Get CCV encrypt
      let getCDVV = await TxEncrypt(cipherTextKkey, tlpy_e_cvv);

      //On change card clean
      let hash_code = document.getElementById('token_hash_code_tilopay');
      //if all is ok
      if (typeof getCDVV != "undefined" && getCDVV != "") {
        cipherCvv = getCDVV;
        if (hash_code) {
          hash_code.value = getCDVV;
        }
        //Clean
        if (errorSdkLi) {
          errorSdkLi.style.transition = "opacity 1s";
          errorSdkLi.style.opacity = 0;

          // Delete "error-sdk-li" after fadeout
          errorSdkLi.addEventListener("transitionend", function (event) {
            if (event.propertyName === "opacity") {
              errorSdkLi.remove();
              tpay_sdk_error_div.style.display = "none";
            }
          });
        }

      } else {
        //error
        // Show element id "tpay-sdk-error-div"
        tpay_sdk_error_div.style.display = "block";
        let showMs = 10;
        if (errorSdkLi) {
          errorSdkLi.style.transition = "opacity 1s";
          errorSdkLi.style.opacity = 0;

          // Delete "error-sdk-li" after fadeout
          errorSdkLi.addEventListener("transitionend", function (event) {
            if (event.propertyName === "opacity") {
              errorSdkLi.remove();
            }
          });
          showMs = 1000;
        }
        setTimeout(() => {
          //Get element id "tpay-sdk-error"and add element "li" with id "error-sdk-li"
          var tpaySdkError = document.getElementById("tpay-sdk-error");
          var newLi = document.createElement("li");
          newLi.id = "error-sdk-li";
          newLi.textContent = tilopayConfig.cardError;
          tpaySdkError.appendChild(newLi);

        }, showMs);
      }
    }
  }

  return { cipherCardNumber, cipherCvv };
}
