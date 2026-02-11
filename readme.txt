=== OpenPix for WooCommerce ===
Contributors: sibeliusseraphini
Tags: woocommerce, openpix, payment, pix
Requires at least: 4.0
Tested up to: 6.9
Requires PHP: 7.3
Stable tag: 2.13.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept Pix payments with real-time updates and seamless checkout.

== Description ==

The Pix Plugin for WooCommerce – OpenPix allows your customers to make payments using Pix within your online store, 24 hours a day, 7 days a week, via QR code or "copy and paste".

It's practical, fast, and secure for both the customer and your store. **Payment confirmation is done in real-time**, which increases your e-commerce conversions and reduces costs with bank slips and credit cards.

## **ADVANTAGES OF THE PIX PLUGIN FOR WOOCOMMERCE - OPENPIX**

* Real-time update after payment
* More conversions
* Deposits via Pix to your business account
* One-click integration
* WebHook: Real-time payment notification
* More autonomy for charges and payments
* Chat support
* Unlimited transactions
* No monthly fees
* Free of bureaucracy
* Send via WhatsApp, Email and SMS
* Real-time Pix QR Code generation
* Pay only for received Pix
* Recurring billing and subscriptions
* Installment Pix
* Native anti-fraud at no additional cost
* Dashboard to track all transactions and bank reconciliation

## **START ACCEPTING PIX IN YOUR WOOCOMMERCE AND OFFER THE MOST PRACTICAL PAYMENT OPTION ON THE MARKET**

1 - Install the Pix WooCommerce - OpenPix plugin on your WordPress site
2 - Create an account at OpenPix [Create account](https://openpix.com.br/register?tags=from-woocommerce)
3 - Copy the Client ID from your OpenPix account and click "Integrate with one click"

Done! After these steps your online store can accept Pix payments

If you need help you can talk to our support via chat or access the [documentation.](https://developers.openpix.com.br/docs/ecommerce/woocommerce-plugin)

## **FREQUENTLY ASKED QUESTIONS**

**DOES THE PLUGIN HAVE A COST OR MONTHLY FEE?**

No, the plugin is 100% free, you only pay a percentage of **0.8% per received Pix.**

**WHAT CONFIGURATION IS REQUIRED TO USE THE PLUGIN?**

- Have WordPress 4.0 or higher installed;
- Have WooCommerce plugin 3.0 or higher installed;
- Use PHP version 7.3 or higher;
- Have an active account at OpenPix

**CAN I USE THE PIX PLUGIN TOGETHER WITH OTHER PAYMENT GATEWAYS?**

Yes, the Pix plugin for WooCommerce - OpenPix can be used together with other complementary gateways such as card processors, bank slips, etc.

**WHERE ARE RECEIVED TRANSACTIONS DEPOSITED?**

Transactions are deposited directly to the Pix key linked to your business account every day. In the OpenPix dashboard, under "Accounts" you can customize deposit settings.

**HOW TO CONFIRM PAYMENT BY PIX?**

The Plugin automatically informs WooCommerce of the payment status, but if you need to verify and confirm a specific transaction just access your OpenPix dashboard under "Transactions".

Within the platform you will find all generated transactions and can view all details, make refunds and much more.

**SUPPORT**

For questions related to integration and plugin, access the [OpenPix Developer Portal](https://developers.openpix.com.br/)

If you need to talk to our team, access the chat available on our website.

== Screenshots ==

1. Example of Plugin configuration
2. Example of Payment Order with Pix QR Code
3. Example of Payment Order with Applied Giftback
4. Example of Paid Payment Order with Earned Giftback
5. Example of Expired Payment Order

== Changelog ==

= 2.13.5 - 2026-02-01 =

- **security:** Added nonce verification to AJAX handlers (openpix_prepare_oneclick, openpix_crediary_prepare_oneclick, openpix_parcelado_prepare_oneclick)
- **security:** Added capability checks (manage_woocommerce) to protect gateway settings reset functionality
- **security:** Fixed CSRF vulnerability in one-click integration buttons

= 2.13.4 - 2026-01-27 =

- **change-log:** v2.13.3 (#1427) (180d5569)
- tag release workflow (#1433) (e34b951e)
- **boleto:** add new payment support boleto (#1431) (7deb3634)
- add contributors own of plugin (#1434) (ea7c8952)
- improve changelog script to update readme.txt (#1432) (8f9fd9a7)
- publish tag (#1430) (91de68b5)
- update changelog yml (#1429) (cad40aed)
- improve svn release and changelog script (#1428) (db34ee8e)

= 2.13.2 - 2025-06-30 =

- Refactored sandbox flow for environment switching
- Added `plugin_dir_path` for configuration file imports

= 2.12.1 - 2025-04-17 =

- Fixed phone validation using `libphonenumber`

= 2.12.0 - 2025-03-05 =

- Support for WordPress block editor.
- Performance improvements.

= 2.11.0 - 2024-11-13 =

- Increased Pix connection timeout.
- Performance improvements.

= 2.10.10 - 2024-03-15 =

- Show Pix QR code in order email.
- Payment block for block editor.

= 2.10.9 - 2024-02-22 =

- Add action to order emails.
- Show Pix QR code in order email.

= 2.10.8 - 2023-12-21 =

- Improve performance.
- Support older PHP versions.

= 2.10.7 - 2023-11-13 =

- Improve performance.
- Support newer WooCommerce and WordPress versions.

= 2.10.6 - 2023-11-10 =

- Better HPOS support.

= 2.10.5 - 2023-11-06 =

- Add better support for legacy order storage mode in Woovi Parcelado and Pix Crediario payment methods.

= 2.10.4 - 2023-11-06 =

- Add better support for legacy order storage mode.

= 2.10.3 - 2023-11-03 =

- Add `:orderId` variable to WooCommerce redirect functionality.

= 2.10.2 - 2023-10-31 =

- Initialize plugin only once.

= 2.10.1 - 2023-10-26 =

- Added HPOS compatibility.
- Show QR Code when my account page has a non-English URL.
- Remove duplicate payment gateways on my account page.

= 2.10.0 - 2023-10-19 =

* Add new payment method with Pix Crediario.

= 2.9.1 - 2023-10-17 =

* Add new parameters `order_id` and `key` to the URL during redirect when a charge is paid.

= 2.9.0 - 2023-10-09 =

* Allow redirecting user when a charge is paid.

= 2.8.1 - 2023-09-14 =

* Integration improvement

= 2.8.0 - 2023-08-29 =

* Giftback feature removal

= 2.7.1 - 2023-08-10 =

* Assets fix

= 2.7.0 - 2023-08-10 =

* Import fixes

= 2.6.2 - 2023-07-19 =

* Fixed OpenPix Parcelado feature classes

= 2.6.1 - 2023-07-19 =

* Added OpenPix Parcelado feature

= 2.6.0 - 2023-05-02 =

* Added refund reason to orders

= 2.5.0 - 2023-04-27 =

* Added order refunds

= 2.4.0 – 2023-04-20 =

* Plugin configuration improvement
* Webhook receiving improvement

= 2.3.0 – 2023-04-03 =

* Improved plugin display in NextMove and Elementor plugins
* Improved security for order approval
* Improved webhook integration

= 2.2.0 – 2023-03-16 =

* Plugin display improvement
* Improved Copy and Paste QR Code function

= 2.1.9 – 2023-03-16 =

* Plugin display improvement

= 2.1.8 - 2023-03-14 =

* Checkout plugin improvement and optimization
* Dependencies update

= 2.1.7 - 2023-03-14 =

* Checkout plugin improvement and optimization
* Dependencies update

= 2.1.6 - 2023-03-14 =

* Endpoints improvement

= 2.1.5 - 2023-02-23 =

* Plugin description improvement
* Improved Copy and Paste QR Code function

= 2.1.4 - 2022-10-25 =

* Error improvement

= 2.1.3 - 2022-06-08 =

* Error improvement
* Coupon application improvement

= 2.1.2 - 2022-05-24 =

* Phone integration improvement
* Added QR Code to order view
* Added payment link to order edit

= 2.1.1 - 2022-04-04 =

* Giftback coupon improvement
* Logs improvement
* Added tests

= 2.1.0 - 2022-04-01 =

* Giftback Coupon
* Improved logs

= 2.0.3 - 2022-02-18 =

* Plugin logs improvement

= 2.0.2 - 2022-02-01 =

* Plugin events improvement

= 2.0.1 - 2022-02-01 =

* New Payment Order UI

= 2.0.0 - 2022-01-31 =

* New Payment Order UI

= 1.12.0 - 2021-11-09 =

* Pix comment and images improvement

= 1.11.0 - 2021-11-09 =

* Order update improvement when confirming Pix payment

= 1.10.0 - 2021-11-08 =

* Webhook automatic configuration improvement
* Order number in additional information

= 1.9.0 - 2021-10-29 =

* Automatic webhook integration configuration

= 1.8.0 - 2021-09-23 =

* Allow UI update when payment is received

= 1.7.0 - 2021-09-21 =

* Allow customizing order status after Pix is paid
* Customer error improvement

= 1.6.1 - 2021-08-25 =

* Order data validation improvement

= 1.6.0 - 2021-08-03 =

* Customer CPF/CNPJ handling improvement

= 1.5.0 - 2021-08-03 =

* Customer handling improvement for an order
* To save the customer we recommend using the plugin [woocommerce-extra-checkout-fields-for-brazil](https://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/)

= 1.4.0 - 2021-07-12 =

* Pix comment improvement

= 1.3.0 - 2021-06-29 =

* More robust Webhook/IPN

= 1.2.0 - 2021-05-27 =

* Webhook/IPN improvement

= 1.1.0 =

* More robust cents logic
* Allow order status customization based on Pix status

= 1.0.1 =

* Responsive improvements.

= 1.0.0 =

* Initial plugin version.
