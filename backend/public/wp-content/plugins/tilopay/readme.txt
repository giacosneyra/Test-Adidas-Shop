=== Tilopay ===
Contributors: hnanne
Tags: ecommerce, woocommerce, central america, caribbean, payment gateway
Requires at least: 3.9
Tested up to: 6.8.2
Stable tag: 3.1.2
Requires PHP: 7.4
Requires Plugins: woocommerce
WC requires at least: 8.0.0
WC tested up to: 10.1.2
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Woo: true

Accept payments on WooCommerce stores with seamless integration, multi-currency support, and advanced tools for secure payment processing.

== Description ==

Accept credit/debit cards, **BNPL**, financing and other local payment methods on your store. Tilopay is an ecosystem of digital payments that uses the most advanced technology to boost your business' transactions. Our solution has been developed to solve online payments for businesses in the Retail, Hospitality and Service industries in Central America and the Caribbean.

### Important update notice:
Before updating the production website, we recommend testing it in your staging environment. You can get your integration key, API user and API password, from [Tilopay admin](https://app.tilopay.com/admin/checkout)

### Why use Tilopay
* Facilitate your clients’ transactions with onsite payments without redirection
* Maximize the approval rate to more than 90% thanks to our processing optimization tools
* Offer a variety of payment methods including the main credit and debit cards (Visa, Mastercard and American
* Express as well as local alternatives such as SINPE Móvil (Costa Rica), Yappy (Panamá), Tasa Cero BAC Credomatic (Central America) and Apple Pay among others
* Provide peace of mind to your consumers by offering state-of-the-art security that includes PCI, 3DS and KOUNT
* Make your business’ recurring charges more flexible with our integration with WooCommerce Subscriptions and with secure card storage (tokenization)

### Top Features:
- LATAM-focused (CAM/CAR) payment facilitation with local bank settlements
- On-site payment with no redirect
- Card-on-file functionality
- Alternative payment methods (wallets, bank transfers, BNPL, etc.)
- Smart Routing for optimal approval rates and antifraud protection (3DS 2.0, Kount Command, Decision Manager and/or ClearSale)

== Installation ==

= Requieres WooCommerce =

= Modern Way: =
1. Go to the WordPress Dashboard "Add New Plugin" section.
2. Search For "Tilopay".
3. Install, then Activate it.
4. Once activated, click on “Settings” of TiloPay or go to WooCommerce settings, payments, click on Tilopay.
5. Follow the [Documentation on Tilopay admin for WooCommerce](https://app.tilopay.com/admin/guide/)

= Old Way: =
1. Upload `tilopay` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Once activated, click on “Settings” of TiloPay or go to WooCommerce settings, payments, click on Tilopay.

## Plugin Configuration Guide

After activating the plugin, you will be redirected to the screen where you can see all installed plugins. Here, click on **"Settings"** to configure the plugin.

### Plugin Settings

#### Enable/Disable
This option allows you to enable or disable the plugin.

#### Title
Modify the name of the **Tilopay payment method** on the purchase confirmation page.

#### API Key
Enter the **integration key** generated through the Tilopay user portal when associating a payment method with the integration.
- **Test Key**: `6609-5850-8330-8034-3464`

#### API User
Enter the **integration user** generated through the Tilopay user portal.
- **Test User**: `lSrT45`

#### API Password
Enter the **integration password** generated through the Tilopay user portal.
- **Test Password**: `Zlb8H9`

---

### Capture Mode

#### **"Do not capture"**
Select «Do not capture» if you want to collect the order in two steps, first authorize and then capture. By selecting this option, the orders will enter with statuses «Pending payment» or «Waiting», the payment of the order will only be authorized. To capture the payment, the order status must be changed to «Processing» manually in the order itself. The maximum date to capture the payment is 7 calendar days after authorization. After the 7 days mentioned without making the capture, the authorized payment will be automatically cancelled.

#### **"Yes, capture"**
Select «Yes, capture» if you want to authorize and capture the payment of the order in a single step and automatically. By selecting this option, the orders will enter with statuses «Processing» or «Completed», this status can be chosen depending on the need of the configuration, and the payment of the order will be authorized and captured automatically.

---

### Configure Logos
Select which card logos to display:
- BAC Minicuotas logo
- BAC Tasa Cero logo
- Choose all or only the ones you prefer.

---

### Integrated Payment Options

#### **Redirect to the Payment Form**
Redirects users to an **external payment page** to complete their transaction.

#### **Native Payment Form**
The payment form is **integrated directly into the WooCommerce checkout** for a seamless experience.

---

### Save Your Settings
Once all configurations are set, click on **"Save Changes"** to apply the settings.

== Frequently Asked Questions ==

= Where can I find support =

Send your support request to sac@tilopay.com

== Screenshots ==
1. Tilopay
2. Add a new plugin
3. Search for Tilopay and install it
4. Click on Activate
5. Click on Settings
6. Fill out the form
7. Select your preferred option: redirect to the payment form or use the native payment form
8. Preview of the native payment form

== Changelog ==
= 2025-10-01 - version 3.1.2 =
* Multi-site and single-site support
= 2025-10-01 - version 3.1.1 =
* Revert changes from 3.1.0
= 2025-09-17 - version 3.1.0 =
* Multi-site support
= 2025-09-04 - version 3.0.9 =
* Card length to cipher AMEX >=15 digits
= 2025-02-05 - version 3.0.8 =
* Yappy payment method on native
* Check if subscription to include for redirect options
* Add screenshots
* Update description
* And others changes
= 2024-11-05 - version 3.0.7 =
* Include in white list Cardinal url to make payments
= 2024-10-24 - version 3.0.6 =
* Remove link from WooCommerce sidebar menu
* Add documentations link
* Remove get_error_message from errors to log all context errors
= 2024-10-21 - version 3.0.5 =
* WooCommerce certifications feedback
= 2024-10-15 - version 3.0.4 =
* Priority change for hook init, so that WOO can detect the change in the order status and send email alerts
* Remove option to force alert email
= 2024-10-09 - version 3.0.3 =
* Apple Pay Button
* Allow trying to force mail sending using WOO hooks
* Other changes in configuration and log cleaning
= 2024-08-20 - version 3.0.2 =
* Check order payment method to check if not tilopay, stop processing
= 2024-08-20 - version 3.0.1 =
* Validate function exists: wcs_is_subscription and wcs_order_contains_subscription
= 2024-08-14 - version 3.0.0 =
* Upgrading to make WooCommerce blocks compatible
* Native use react component to validate, process and show errors at FE
* Fix warning: billing_email was called incorrectly
= 2024-05-03 - version 2.1.2 =
* Fix validations and undefined var.
* Disable encryption validation before order is created to redirect flow.
* If not encrypt force to redirect Tilopay payment form.
* Add order note when is native and encryption failed.
* Check subscriptions type (subscription, variable-subscription)
* Fix payment on pay-order WC
* Validate if function_exists wc_add_notice or wc_clear_notices
= 2024-01-08 - version 2.1.1 =
* Fix validations.
* Others notes.
= 2023-12-22 - version 2.1.0 =
* Using $_GET instead of $_REQUEST.
* Get recurrent error response.
= 2023-11-27 - version 2.0.9 =
* Validate if SDK encrypt data card.
* If not encrypt force to redirect Tilopay payment form.
= 2023-11-22 - version 2.0.8 =
* Others styles.
= 2023-11-06 - version 2.0.7 =
* Validate if SDK encrypt data card.
* Redirect to check if hash validation are no same from Tilopay.
* Add default title.
* Use own nonce validations checkout.
* Making compatibility with WOO HPOS or WP posts storage (legacy).
* Spinner on innit call.
= 2023-08-21 - version 2.0.6
* Yoda validations.
* WOO standards.
* Fix $this->get_options('key') function is deprecated, now using $this->settings['key'].
= 2023-07-20 - version 2.0.5 =
* KOUNT implemented for commerce that have it active.
* Validate modifications response, to update order notes.
* Add param hashVersion to request V2.
* Check if notes have already been added.
* Include Tilopay order id at notes.
* Tilopay SDk (V2) integration that only using JavasCript not JQuery.
* Fix custom spinner and remove js overlay.
* Fix endpoints path.
* Checkbox save card required for subscription products before create order.
* Checkbox save card auto check if have product subscription.
* Subscriptions payment is not allowed in test environment.
* Others styles.
= 2023-01-30 - version 2.0.3 =
* Fix to load SDK only for native payment WOO.
* Additional details in the payload to respond based on the website language WP.
* It includes more details to make the order hash validation.
* Some extra CSS.
= 2022-12-16 - version 2.0.2 =
* Fix to avoid wc_add_notice from admin.
* Fix default card IMG.
= 2022-12-06 - version 2.0.1 =
* Load Tilopay front scripts only at checkout and pay_for_order pages.
* Set priority 11 for load_tilopay_front_scripts to fix:
* -JS incompatibility conflicts with "WC Provincia-Canton-Distrito plugin".
* -SDK jQuery incompatibility conflicts.
= 2022-11-08 - version 2.0.0 =
* WOO Direct Gateway or redirect way.
* Implement the Tilopay SDK on checkout page.
* Show the card saved by user email.
* Allow payment with SINPE Móvil.
* Adding spinner js cdn.
* New error handler.
= 2022-08-24 - version 1.3.0 =
* If not capture set payment pending.
* Fix redirect when save url payment form.
* Allow Woocommerce to sort Tilopay position.
* Logo on one row with text.
* Fix front css and remove option to customize icon.
= 2022-07-26 - version 1.2.9 =
* Add Spanish translation files.
= 2022-07-07 - version 1.2.8 =
* New icons and control what icon to show it.
* Grid and flex system.
* Webhook to update orders status.
= 2022-05-05 - version 1.2.7 =
* Fixing WOO stats conflict.
= 2022-05-05 - version 1.2.6 =
* Show message transaction is declined.
= 2022-02-21 - version 1.2.5 =
* Fix log: data was called incorrectly.
* Fix log: the get_refund_amount function is deprecated.
* Remove on init WCTilopay validations.
* Testing WooCommerce Version 6.2.0.
* Adding hash to provee is return from Tilopay server.
* Show message if user cancel the payment process.
* Show message if invalid order confirmation.
* Add order note if order con is invalid.
* Fix translation.
* Remove auto check to set default payment.
* Fixing the order status for recurring payments, according the payment status.
* Remove modal payment.
* For authorization and partial capture mode only show: pending payment and on hold.
= 2022-01-28 - version 1.2.4 =
* Remove URL validation.
= 2022-01-28 - version 1.2.3 =
* Make URL array to validate.
* Add global env url.
= 2022-01-21 - version 1.2.2 =
* logo update.
= 2022-01-21 - version 1.2.1 =
* fixing error space.
= 2022-01-20 - version 1.1.9 =
* fixing error space.
= 2022-01-20 - version 1.1.8 =
* fixing error space.
= 2022-01-20 - version 1.1.7 =
* fixing error space.
= 2022-01-05 - version 1.1.6 =
* adding sweet alert confirm message.
* And others changes.
= 2021-12-15 - version 1.1.5 =
* Fixing responsive CSS.
* And others changes.
= 2021-11-26 - version 1.1.4 =
* Adding options to remove icon to customize it.
* Validate if integration key are valid.
* Fixing responsive CSS.
* And others changes.
= 2021-11-07 - version 1.1.3 =
* Translating Tilopay, EN and ES.
* Customize payment gateway icon and title.
* And others changes.
= 2021-11-01 - version 1.1.2 =
* Using namespace.
* Updating settings.
= 2021-10-24 - version 1.1.1 =
* Adding setting link.
* Using select instead checkbox.
* Updating some validations.
* Adding text domain and security updating.
= 2021-10-21 - version 1.0.0 =
*  Initial release
