=== HDWebmobile Order Quantity Rules ===
Contributors: htrxuan
Donate link: https://paypal.me/htrxuan/20
Tags: woocommerce, minimum order quantity, maximum order quantity, order limits, wholesale
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Minimum, maximum, and step order-quantity rules -- enforced server-side on every checkout path, not just in the cart page's own JS.

== Description ==

HDWebmobile Order Quantity Rules lets you set a store-wide minimum, maximum, and order-multiple ("must be ordered in packs of N") quantity, with the option to override any of these on an individual product.

= Why this plugin exists =
Several competing minimum/maximum order quantity plugins enforced their rules only in the cart page's own JavaScript and in a single add-to-cart check, but never re-validated the cart at the point an order is actually allowed to proceed (CWE-602, Client-Side Enforcement of Server-Side Security). Editing a cart-item quantity directly -- a raw "update cart" request, or a call to the block-based Store API's own cart-update endpoint -- skipped the rule entirely.

This plugin closes that by re-checking every product's total quantity in the cart at `woocommerce_check_cart_items`, the one hook WooCommerce itself fires on every cart page load, at classic checkout, and inside the Store API's own cart and checkout processing alike. A quantity that only ever got into the cart by skipping the add-to-cart check is still caught there, every single time -- there is no path to a completed order that bypasses it.

= Key Features =
* Store-wide default minimum, maximum, and step (order-multiple) quantity
* Per-product overrides on the Inventory tab of any product
* Enforced identically on classic and block-based (Store API) cart and checkout
* A quantity hint shown right on the product page

= Limitations (please read before installing) =
* Rules apply per simple product, not per-variation
* No per-customer-role exceptions in this version

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/hdwebmobile-order-quantity-rules` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress. WooCommerce must already be installed and active.
3. Go to **WooCommerce > HDWebmobile > Quantity Rules** to set your store-wide defaults.

== How to Use ==

= 1. Set store defaults =
Configure a default minimum, maximum, and step quantity that applies to every product.

= 2. Override per product =
Edit any product and set its own min/max/step on the General tab -- leave a field blank to keep the store default.

== Screenshots ==

1. The store-wide default settings.
2. Per-product overrides on the product edit screen.

== Changelog ==

= 1.0.0 =
* Initial release: store-wide and per-product min/max/step quantity rules, enforced at `woocommerce_check_cart_items` so no checkout path can bypass them.
