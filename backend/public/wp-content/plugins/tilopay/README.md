# Tilopay - WooCommerce Payment Gateway

**Tilopay** accepting **credit and debit card payments** on WooCommerce stores. It offers seamless integration, multi-currency support, and advanced tools for secure and efficient payment management.

---

# 🚀 Guide to Build WooCommerce Block Changes (Tilopay)

Whenever you modify the JavaScript files (`src/js/frontend`) of the **Tilopay** plugin, follow these steps to compile the changes and generate the final package:

---

## Step 1. Navigate to the plugin directory
```bash
cd wp-content/plugins/tilopay
```

## Step 2 – Install Dependencies
```bash
npm i && composer i
```

## Step 3 – Compile the Changes

### Local development (watch mode)
```bash
npm run start
```

### Production build
```bash
npm run build
```

## Step 4 – Package the Plugin
```bash
zip -r tilopay.zip tilopay
```

---

## Description

With **Tilopay**, your WooCommerce store can accept online payments with credit and debit cards, adapting to the needs of multiple countries and currencies. This plugin is ideal for businesses in **Central America** and the **Caribbean** seeking a modern payment gateway with advanced features like partial refunds, full/partial captures, and **3D Secure** security.

🔗 **Plugin Page:** [Tilopay on WordPress](https://wordpress.org/plugins/tilopay/)
🔗 **Full Documentation:** [Tilopay Configuration Guide](https://tilopay.com/documentacion/plataforma-woocommerce)

---

## Key Features

- **Multi-Country**: Supports multiple countries in a single integration.
- **Multi-Currency**: Accepts payments in various currencies.
- **Multi-Affiliate**: Manage multiple affiliates for efficient sales tracking.
- **Refunds**: Supports both **partial** and **full refunds**.
- **Captures**: Allows **partial** and **full payment captures**.
- **3D Secure**: Adds an extra layer of security for customer authentication.
- **Kount Integration**: Advanced fraud protection.
- **Fully compatible**: with the latest **WooCommerce** and **WordPress** versions.
- **WooCommerce plugin**: Certificate.

---

## Technical Details

```text
/*
 * Plugin Name: Tilopay
 * Plugin URI: https://wordpress.org/plugins/tilopay/
 * Description: Accept credit and debit cards on your WooCommerce Store.
 * Requires Plugins: WooCommerce
 * Author: Tilopay
 * Author URI: https://tilopay.com
 * WC requires at least: 8.0.0
 * Tested up to: 6.6.2
 * License: GPLv2
 * Text Domain: tilopay
 * Domain Path: /languages
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 */
```

**Use Yoda validations** for any customization, example: `if (false === $resultado) {// code here}`
