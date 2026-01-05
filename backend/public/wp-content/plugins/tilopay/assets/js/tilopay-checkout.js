/**
 *
 * This file is implemented
 * From Tilopay plugin V 2.0.3
 * more info tilopay.com
 *
 */
jQuery(document).ready(function ($) {
  let searchParams = new URLSearchParams(window.location.search);
  let tlpy_payment_order = "";
  let payment_method_selected = "";
  if (
    searchParams.has("process_payment") &&
    searchParams.has("tlpy_payment_order") &&
    searchParams.has("tlpy_payment_method")
  ) {

    //Show loader
    showTilopaySpinner();

    tlpy_payment_order = searchParams.get("tlpy_payment_order");
    let get_tlpy_payment_method = atob(searchParams.get("tlpy_payment_method")).split("|");
    payment_method_selected = get_tlpy_payment_method[0];
    if (get_tlpy_payment_method[1] == "1") {
      $("#terms").prop("checked", true);
    }

    let checkQueryCallback = tilopayConfig.redirect;
    if (checkQueryCallback.includes('?')) {
      checkQueryCallback = checkQueryCallback + '&selected_method=SINPEMOVIL'; //add flag to validate
    } else {
      checkQueryCallback = checkQueryCallback + '?selected_method=SINPEMOVIL'; //add flag to validate
    }

    tilopayConfig.orderNumber = tlpy_payment_order;
    tilopayConfig.redirect = checkQueryCallback;
    tilopayConfig.payment_method_selected = payment_method_selected;
  }
  //check if woo is set
  if (typeof wc_checkout_params === "undefined") return false;

  setTimeout(() => {
    $("#tlpy_cc_number").css({
      "background-image": "url(" + tilopayConfig.tpayPluginUrl + "assets/images/flat_icon_tilopay.png)",
      "background-repeat": "no-repeat",
      "background-position": "right 0.6180469716em center",
      "background-size": "31px 20px"
    });
  }, 200);

  //check if checkout page wc_checkout_params.is_checkout

  //change one of this input attemp place order to get order number
  jQuery(document).on("change", "#tlpy_cc_number, #tlpy_cc_expiration_date, #tlpy_cvv", async function (e) {
    //makeCipherData;
    let response = makeCipherData();
    //call to validate input and get cipher
  });

  //set card icon
  jQuery(document).on("change keyup paste", "#tlpy_cc_number", function (e) {
    if (
      $(this).val() !== "" &&
      ($(this).val().length == 3 || $(this).val().length >= 15)
    ) {
      set_card_icon();
    }
    //
    if ($(this).val() === "") {
      $("#tlpy_cc_number").css({
        "background-image": "url(" + tilopayConfig.tpayPluginUrl + "assets/images/flat_icon_tilopay.png)",
        "background-repeat": "no-repeat",
        "background-position": "right 0.6180469716em center",
        "background-size": "31px 20px"
      });
    }
  });

  let checkEmail = "";
  //on change billing_email
  $("form.checkout").on("change", "#billing_email", function () {
    checkEmail = $(this).val();
    if (checkEmail != "") {
      initSDKTilopay();
    }
  }); //.end on change billing_email

  let radioTilopayOption = document.getElementById("payment_method_tilopay");
  let isTilopaySelected = (radioTilopayOption && radioTilopayOption.checked) ? true : false;

  //Set timout to wait dom load 100%
  setTimeout(() => {
    if (isTilopaySelected) {
      let btnWooCheckOutW = document.getElementById("place_order");
      btnWooCheckOutW.setAttribute('type', 'button');
    }
  }, 3000);

  //click event handler close modal SINPE for event listener clic
  setTimeout(() => {

    document.addEventListener("click", function (e) {
      const tgt = e.target;

      //Event listener to check if Tilopay and change BTN attribute to validate kount
      if (tgt.closest('.wc_payment_method')) {
        const getRadioBtns = tgt.closest('.wc_payment_methods').querySelectorAll('.input-radio');

        if (getRadioBtns) {

          //Get checkout BTN WOO to change attribute type
          let btnWooCheckOut = document.getElementById("place_order");

          getRadioBtns.forEach(radioBtn => {

            radioBtn.addEventListener('click', function () {

              if (radioBtn.checked) {

                if (radioBtn.value === 'tilopay') {
                  btnWooCheckOut.setAttribute('type', 'button');
                } else {
                  btnWooCheckOut.setAttribute('type', 'submit');
                }

              }

            });

          });
        }
      }

      //If payment with Tilopay check if have kount
      if (tgt.id === 'place_order' && tgt.type === 'button') {
        tgt.classList.add("loading");
        checkHaveKount();
      }

      if (tgt.classList.contains("payWithSinpeMovil")) {
        //on open modal check
        document.querySelector(tgt.dataset.modal).classList.add("active");
      } else if (
        tgt.classList.contains("tilopay-overlay") ||
        tgt.classList.contains("btn-tilopay-close-modal")
      ) {
        //close modal on click out of modal or btn
        tgt.closest(".tilopay-modal-container").classList.remove("active");
        if (typeof myInterval != 'undefined') {
          clearInterval(myInterval);
        }
      }

    });
  }, 1000);

}); //.end ready

async function set_card_icon() {
  let typeCard = "url(" + tilopayConfig.tpayPluginUrl + "assets/images/flat_icon_tilopay.png)"
  var type = await Tilopay.getCardType();
  if (typeof type.message !== undefined) {
    document.getElementById('card_type_tilopay').value = type.message;
    switch (type.message) {
      case "visa":
        typeCard =
          "url(" + tilopayConfig.tpayPluginUrl + "assets/images/visa.svg)";
        break;
      case "mastercard":
        typeCard =
          "url(" + tilopayConfig.tpayPluginUrl + "assets/images/mastercard.svg)";
        break;
      case "amex":
        typeCard =
          "url(" + tilopayConfig.tpayPluginUrl + "assets/images/american_express.svg)";
        break;
      default:
        typeCard = "url(" + tilopayConfig.tpayPluginUrl + "assets/images/flat_icon_tilopay.png)"
        break;
    }
  }

  var tlpyCcNumberElement = document.getElementById('tlpy_cc_number');
  tlpyCcNumberElement.style.backgroundImage = typeCard;
  tlpyCcNumberElement.style.backgroundRepeat = "no-repeat";
  tlpyCcNumberElement.style.backgroundPosition = "right 0.6180469716em center";
  tlpyCcNumberElement.style.backgroundSize = "31px 20px";

}

function onchange_select_card() {
  //Clean inputs
  cleanCardForm();
  if (document.getElementById('cards').value !== "") {
    if (document.getElementById('cards').value === "newCard" || document.getElementById('cards').value === "0") {
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

async function initSDKTilopay() {
  document.getElementById("loaderTpay").style.display = "flex";
  //Clean inputs
  let getResponse = makeCipherData();
  let woo_session_tilopay = document.getElementById('woo_session_tilopay');
  if (woo_session_tilopay) {
    woo_session_tilopay.value = tilopayConfig.wooSessionTpay;
  }

  let payment_method_selected =
    typeof tilopayConfig.payment_method_selected != "undefined"
      ? tilopayConfig.payment_method_selected
      : "";

  let getInputEmail = document.getElementById('billing_email').value;
  //check if email at input are same that we pass from BE
  if (getInputEmail != "" && tilopayConfig.billToEmail != getInputEmail) {
    tilopayConfig.billToEmail = getInputEmail;
  }

  var initialize = await Tilopay.Init(tilopayConfig);
  //if environment not set
  if (
    typeof initialize.environment != "undefined" &&
    initialize.environment != "PROD"
  ) {
    document.getElementById('tpay_env').value = initialize.environment;
    document.getElementById('environment').style.display = 'block';
    document.getElementById('environment').innerHTML = tilopayConfig.envMode + ' <a href="http://admin.tilopay.com/">Admin Tilopay</a>';
    document.getElementById('environment').classList.add('tpayEnvAlert');

  }

  if (
    typeof initialize.message != "undefined" &&
    initialize.message == "Success"
  ) {
    let paymentMethods = initialize.methods.length;
    let onlyOneMethod = 0;
    if (paymentMethods > 0) {
      var methodSelect = document.getElementById('tlpy_payment_method');
      var get_options = methodSelect.querySelectorAll('option:not(:first-child)');
      get_options.forEach(function (option) {
        option.remove();
      });

      //method
      initialize.methods.forEach(function (method, index) {
        var option = document.createElement('option');
        option.value = method.id;
        option.text = method.name;
        option.selected = index == 0;
        methodSelect.appendChild(option);
      });
      //get if sinpe the first
      onlyOneMethod = initialize.methods[0].id.split(":")[1];

      //select firstone if only one
      if (paymentMethods == 1 && payment_method_selected == "") {
        document.getElementById('tlpy_payment_method').value = initialize.methods[0].id;
        document.getElementById('tlpy_payment_method').style.display = 'none';
        document.getElementById('methodLabel').style.display = 'none';

        if (onlyOneMethod === "4") {
          document.getElementById('selectCard').style.display = 'none';
          document.getElementById('pay_sinpemovil_tilopay').value = 1;
          document.getElementById('divTpaySinpeMovil').style.display = 'block';
          document.getElementById('divTpayCardForm').style.display = 'none';
        }
      } else {
        document.getElementById('tlpy_payment_method').style.display = 'block';
        document.getElementById('methodLabel').style.display = 'block';
      }

      if (payment_method_selected != "") {
        document.getElementById('pay_sinpemovil_tilopay').value = 1;
        document.getElementById('selectCard').style.display = 'none';
        document.getElementById('tlpy_payment_method').value = payment_method_selected;

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
      }

      //Check test mode and have suscription
      if (tilopayConfig.haveSubscription == '1' && initialize.environment !== "PROD") {
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

    tilopayConfig.haveCard = countCard;
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
      newOption.text = tilopayConfig.newCardText;
      document.getElementById("cards").appendChild(newOption);

      document.getElementById("tpay_save_card").disabled = false;
      document.getElementById("tpay_save_card").checked = false;
      document.getElementById('divSaveCard').style.display = 'none';
    } else {
      document.getElementById('selectCard').style.display = 'none';
      if (document.getElementById('tpay_env').value === "PROD") {
        document.getElementById('divSaveCard').style.display = 'block';
        if (tilopayConfig.haveSubscription === '1') {
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
  } else {
    //erro
    document.getElementById('tpay-sdk-error-div').style.display = 'block';
    const errorSdkLi = document.getElementById("error-sdk-li");
    if (errorSdkLi) {
      errorSdkLi.style.opacity = 1;
      const fadeOutDuration = 1000;
      const fadeOutInterval = 50;
      const fadeOutSteps = fadeOutDuration / fadeOutInterval;
      let currentStep = 0;

      const fadeOutIntervalId = setInterval(function () {
        currentStep++;
        errorSdkLi.style.opacity = 1 - (currentStep / fadeOutSteps);

        if (currentStep === fadeOutSteps) {
          clearInterval(fadeOutIntervalId);
          errorSdkLi.parentNode.removeChild(errorSdkLi);
        }
      }, fadeOutInterval);
    }

    const errorSdkNewLi = document.createElement("li");
    errorSdkNewLi.id = "error-sdk-li";
    errorSdkNewLi.textContent = tilopayConfig.integrationError;
    document.getElementById("tpay-sdk-error").appendChild(errorSdkNewLi);
  }

  document.getElementById("loaderTpay").style.display = "none";
}

function onchange_payment_method(selectObject) {
  //get sinpemovil
  let valSelected = selectObject.value;
  let isSinpeMovil = valSelected.split(":")[1];
  if (isSinpeMovil == "4") {
    document.getElementById('selectCard').style.display = 'none';
    document.getElementById('pay_sinpemovil_tilopay').value = 1;
    document.getElementById('divTpaySinpeMovil').style.display = 'block';
    document.getElementById('divTpayCardForm').style.display = 'none';
  } else {
    document.getElementById('pay_sinpemovil_tilopay').value = 0;
    document.getElementById('divTpaySinpeMovil').style.display = 'none';
    document.getElementById('divTpayCardForm').style.display = 'block';
    if (tilopayConfig.haveCard > 0) {
      document.getElementById('selectCard').style.display = 'block';
    }
  }
}

// Show Tpay Loader
function showTilopaySpinner() {
  // Create element spinner
  var spinnerTilopay = document.createElement("div");
  spinnerTilopay.id = "spinner_Tilopay";
  spinnerTilopay.className = "spinner_Tilopay";

  // Add the spinner to document body
  document.body.appendChild(spinnerTilopay);
  // Load CSS
  addTilopaySpinnerCSS();
}

// Hide Tpay
function hideTilopaySpinner() {
  var spinnerTilopay = document.getElementById("spinner_Tilopay");
  if (spinnerTilopay) {
    // Remove spinner from DOM if exist
    spinnerTilopay.parentNode.removeChild(spinnerTilopay);
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

function applyValidationBE(kountId, kountEnv) {
  const formulario = document.forms["checkout"];
  const formData = new FormData(formulario);

  const xhr = new XMLHttpRequest();

  // Endpoint to make the request AJAX
  xhr.open("POST", "/wp-json/tilopay/v1/tpay_validate_checkout_form_errors");
  xhr.responseType = "json";

  xhr.onload = function () {
    if (xhr.status === 200) {
      // Ger response
      const response = xhr.response;
      //If we get true we init KOUNT
      if (response) {
        //call Kount
        kountInit(kountId, kountEnv);

      } else {
        //If we get false, make the request ti BE to show errors
        //console.log(xhr.statusText);
        sendFormToBE();
      }
    } else {
      //If we get false, make the request ti BE to show errors
      //console.log(xhr.statusText);
      sendFormToBE();
    }
  };

  xhr.onerror = function () {
    //If we get false, make the request ti BE to show errors
    //console.log(xhr.statusText);
    sendFormToBE();
  };

  // Process AJAX request
  xhr.send(formData);
}

function kountInit(kountId, kountEnv) {
  var kountConfig = {
    "clientID": kountId,
    "environment": kountEnv,
    "isSinglePageApp": false,
    "isDebugEnabled": false
  }

  const initKountSDK = kountSDK(kountConfig, tilopayConfig.wooSessionTpay);

  if (initKountSDK) {
    //console.log("Anti-fraud activated!");
    //Send form KOUNT is init
    sendFormToBE();
  } else {
    //Send form also if KOUNT is not get init to no stop sale
    sendFormToBE();
  }

}


function sendFormToBE() {
  //Get BTN to cahnge attribute type
  let btnWooCheckOut = document.getElementById("place_order");

  //Set submit type and trigger click event
  btnWooCheckOut.setAttribute('type', 'submit');
  btnWooCheckOut.click();

  //Once is trigged change BTN type
  btnWooCheckOut.setAttribute('type', 'button');
  setTimeout(() => {
    btnWooCheckOut.classList.remove("loading");
  }, 200);
}

function checkHaveKount() {
  let radioTilopayOption = document.getElementById("payment_method_tilopay");
  let isTilopaySelected = (radioTilopayOption && radioTilopayOption.checked) ? true : false;

  //Check have Kount
  var methodSelected = document.getElementById('tlpy_payment_method');
  if (methodSelected && isTilopaySelected) {

    let getMethodSelectedVal = methodSelected.value;
    let splitSelectedMethod = getMethodSelectedVal.split(":");

    if (splitSelectedMethod.length > 4) {

      let useKount = splitSelectedMethod[4];
      let kountId = splitSelectedMethod[5];

      if (useKount !== null && useKount !== undefined && useKount == 2 && kountId !== null && kountId !== undefined) {

        let kountEnv = document.getElementById('tpay_env').value;
        //check form is ok to apply Kount
        applyValidationBE(kountId, kountEnv);

      } else {
        //Do not have KOUNT
        sendFormToBE();
      }
    }

  }
}

function cleanCardForm() {
  //Clean inputs text
  let tlpy_cc_number = document.getElementById('tlpy_cc_number');
  if (tlpy_cc_number) {
    tlpy_cc_number.value = '';
  }

  let tlpy_cc_expiration_date = document.getElementById('tlpy_cc_expiration_date');
  if (tlpy_cc_expiration_date) {
    tlpy_cc_expiration_date.value = '';
  }

  let tlpy_cvv = document.getElementById('tlpy_cvv');
  if (tlpy_cvv) {
    tlpy_cvv.value = '';
  }
  //Clean input hidden
  let hash_card = document.getElementById('token_hash_card_tilopay');
  if (hash_card) {
    hash_card.value = '';
  }
  let hash_code = document.getElementById('token_hash_code_tilopay');
  if (hash_code) {
    hash_code.value = '';
  }
  return true;
}

async function makeCipherData() {
  let tpay_sdk_error_div = document.getElementById("tpay-sdk-error-div");
  // get element id "error-sdk-li" and make fadeout
  var errorSdkLi = document.getElementById("error-sdk-li");

  let tlpy_cc_number = document.getElementById("tlpy_cc_number");
  tlpy_cc_number = (typeof tlpy_cc_number != 'undefined') ? tlpy_cc_number.value : '';

  let tlpy_cc_expiration_date = document.getElementById("tlpy_cc_expiration_date");
  tlpy_cc_expiration_date = (typeof tlpy_cc_expiration_date != 'undefined') ? tlpy_cc_expiration_date.value : '';

  let tlpy_cvv = document.getElementById("tlpy_cvv");
  tlpy_cvv = (typeof tlpy_cvv != 'undefined') ? tlpy_cvv.value : '';

  let selectCard = document.getElementById("cards");
  selectCard = (typeof selectCard != 'undefined') ? selectCard.value : '';

  if ((tlpy_cc_number != "", tlpy_cc_expiration_date != "" && tlpy_cvv != "")) {
    let res = await Tilopay.getCipherData();
    //if all is ok
    if (typeof res.card != "undefined" && res.card != "" && typeof res.cvv != "undefined" && res.cvv != "") {
      let hash_card = document.getElementById('token_hash_card_tilopay');
      if (hash_card) {
        hash_card.value = res.card;
      }
      let hash_code = document.getElementById('token_hash_code_tilopay');
      if (hash_code) {
        hash_code.value = res.cvv;
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
  } else if (tlpy_cvv != "" && selectCard != "newCard") {
    //Get CCV encrypt
    let getCD = await Tilopay.getCipherData();
    //On change card clean
    let hash_code = document.getElementById('token_hash_code_tilopay');
    //if all is ok
    if (typeof getCD.cvv != "undefined" && getCD.cvv != "") {
      if (hash_code) {
        hash_code.value = getCD.cvv;
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
  return true;
}
