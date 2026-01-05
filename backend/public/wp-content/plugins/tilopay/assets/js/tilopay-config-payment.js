jQuery(document).ready(function ($) {
  // var pluging_url = variableSet.pluginsUrl;
  // var payment_icon = pluging_url + '/tilopay/assets/images/tilopay_color.png';


  // var remove_icon_button_text = variableSet.removeIconButtonText;
  // var use_tilo_pay_icon = variableSet.useTiloPayIcon;

  //swal
  var swal_title = variableSet.swalTitel;
  var swal_body = variableSet.swalBody;
  var swal_swal_Btn_cancel = variableSet.swalBtnCancel;
  var swal_Btn_Ok = variableSet.swalBtnOk;
  var swal_no_change = variableSet.swalNoChange;
  var swal_change = variableSet.swalChange;

  //woocommerce_tilopay_tpay_logo_options
  var multiselect_tilopay = document.getElementById('woocommerce_tilopay_tpay_logo_options');
  multiselect_tilopay.setAttribute('multiselect-search', true);
  multiselect_tilopay.setAttribute('multiselect-select-all', true);
  multiselect_tilopay.setAttribute('multiselect-max-items', 8);

  //woocommerce_tilopay_icon
  // $('#woocommerce_tilopay_icon').after(
  //   '<div class="grid-container-tilopay" id="icono-tilopay-div">' +
  //   '<div class="item1">' +
  //   '<img id="icon_tilopay" class="regular-input" src="' + payment_icon + '" style="border: 3px solid #ff3644; border-style: dashed; cursor: pointer; max-width: 70%; max-height: none !important; margin: 0px 150px;">' +
  //   '</div>' +
  //   '<div class="item2">' +
  //   '<button id="remove_icon_button_text" class="button-primary" type="button" style="background: #948375;border-color: #948375;color: #fff;font-size: 15px;margin-left: 180px;">' + remove_icon_button_text + '</button>' +
  //   '</div>' +
  //   '<div class="item3">' +
  //   '<button id="use_tilopay_icon_button" class="button-primary" type="button" style="background: #da001e;border-color: #da001e;color: #fff;font-size: 15px;margin: 0px 0px;">' + use_tilo_pay_icon + '</button>' +
  //   '</div>' +
  //   '</div>'
  // );

  // if ($('#woocommerce_tilopay_icon').val() === 'no_image') {
  //   document.getElementById("icon_tilopay").src = pluging_url + '/tilopay/assets/images/no_image.png';
  //   $('#remove_icon_button_text').hide();
  // } else {
  //   document.getElementById("icon_tilopay").src = ($('#woocommerce_tilopay_icon').val()) ? $('#woocommerce_tilopay_icon').val() : pluging_url + '/tilopay/assets/images/tilopay_color.png';
  // }


  // var custom_uploader;
  // $('#icon_tilopay').click(function (e) {
  //   //var _parent = $(this).parent("tr");
  //   e.preventDefault();

  //   //If the uploader object has already been created, reopen the dialog
  //   if (custom_uploader) {
  //     custom_uploader.open();
  //     return;
  //   }

  //   //Extend the wp.media object
  //   custom_uploader = wp.media.frames.file_frame = wp.media({
  //     title: 'Choose Image',
  //     button: {
  //       text: 'Choose Image'
  //     },
  //     multiple: true
  //   });

  //   //When a file is selected, grab the URL and set it as the text field's value
  //   custom_uploader.on('select', function () {
  //     //console.log(custom_uploader.state().get('selection').toJSON());
  //     attachment = custom_uploader.state().get('selection').first().toJSON();
  //     $('#woocommerce_tilopay_icon').val(attachment.url);
  //     document.getElementById("icon_tilopay").src = attachment.url;
  //     $('#remove_icon_button_text').show();
  //   });

  //   //Open the uploader dialog
  //   custom_uploader.open();

  // });
  //click remove_icon_button_text
  // $('#remove_icon_button_text').click(function (e) {
  //   console.log('click remove_icon_button_text');
  //   $('#woocommerce_tilopay_icon').val('no_image');
  //   document.getElementById("icon_tilopay").src = pluging_url + '/tilopay/assets/images/no_image.png';
  //   $('#remove_icon_button_text').hide();

  // });

  // //use_tilopay_icon_button
  // $('#use_tilopay_icon_button').click(function (e) {
  //   var img_logo = pluging_url + '/tilopay/assets/images/tilopay_color.png';
  //   document.getElementById("icon_tilopay").src = img_logo
  //   $('#woocommerce_tilopay_icon').val(img_logo);
  //   $('#remove_icon_button_text').show();
  // });

  //Set id to tr onload
  $('#woocommerce_tilopay_tpay_capture').closest("tr").attr("id", "tr_tilopay_capture");
  $('#woocommerce_tilopay_tpay_capture_yes').closest("tr").attr("id", "tr_tilopay_capture_yes");

  //Check status
  checkIfCapture($('#woocommerce_tilopay_tpay_capture'), false);
  //Un select change
  $(document).on('change', '#woocommerce_tilopay_tpay_capture', function () {
    //Check status
    checkIfCapture($(this), true);
  });


  //Function helper to cehck what to show it
  function checkIfCapture(target_id, change_select) {

    //Check if capture or not
    if ('yes' === target_id.val()) {
      console.log('yes');
      //Show tr_tilopay_capture_yes
      $('#tr_tilopay_capture_yes').show();
      if (change_select) {
        swal(swal_change, {
          icon: "success",
          timer: 2000,
        });
        $(".woocommerce-save-button").trigger("click");
      }

    } else {
      //check if select changed
      if (change_select) {
        swal({
          title: swal_title,
          text: swal_body,
          icon: "warning",
          buttons: true,
          dangerMode: true,
          buttons: [swal_swal_Btn_cancel, swal_Btn_Ok],
        })
          .then((willDelete) => {
            if (willDelete) {
              //approved 
              $('#tr_tilopay_capture_yes').hide();
              swal(swal_change, {
                icon: "success",
                timer: 2000,
              });
              $(".woocommerce-save-button").trigger("click");
            } else {
              swal(swal_no_change, {
                icon: "success",
                timer: 2000,
              });
              target_id.val('yes');
              //Show tr_tilopay_capture_yes
              $('#tr_tilopay_capture_yes').show();
            }
          });
      } else {
        $('#tr_tilopay_capture_yes').hide();
      }//if select change

    }//.is not capture
  }//.End checkIfCapture

});//.End onready

