# HDWebmobile Order Quantity Rules

Minimum, maximum, and step order-quantity rules -- enforced server-side on every checkout path, not just in the cart page's own JS.

- **WordPress.org:** https://wordpress.org/plugins/hdwebmobile-order-quantity-rules/
- **Requires:** WordPress 6.9+, WooCommerce, PHP 7.4+
- **License:** GPLv2 or later

## Description

Sets a store-wide minimum, maximum, and order-multiple ("must be ordered in packs of N") quantity, with the option to override any of these on an individual product.

## Why this plugin exists

Several competing minimum/maximum order quantity plugins enforced their rules only in the cart page's own JavaScript and in a single add-to-cart check, but never re-validated the cart at the point an order is actually allowed to proceed (CWE-602, Client-Side Enforcement of Server-Side Security). Editing a cart-item quantity directly -- a raw "update cart" request, or a call to the block-based Store API's own cart-update endpoint -- skipped the rule entirely.

This plugin closes that by re-checking every product's total quantity in the cart at `woocommerce_check_cart_items`, the one hook WooCommerce itself fires on every cart page load, at classic checkout, and inside the Store API's own cart and checkout processing alike. A quantity that only ever got into the cart by skipping the add-to-cart check is still caught there, every single time -- there is no path to a completed order that bypasses it.

## Features

* Store-wide default minimum, maximum, and step (order-multiple) quantity
* Per-product overrides on the Inventory tab of any product
* Enforced identically on classic and block-based (Store API) cart and checkout
* A quantity hint shown right on the product page

## Limitations

* Rules apply per simple product, not per-variation
* No per-customer-role exceptions in this version

## Installation

1. Upload to `/wp-content/plugins/hdwebmobile-order-quantity-rules`, or install through the WordPress plugins screen.
2. Activate. WooCommerce must already be installed and active.
3. Go to **WooCommerce > HDWebmobile > Quantity Rules** to set your store-wide defaults.

## Development

```
includes/
  class-hdmoq-activator.php   activation check
  class-hdmoq-core.php        bootstraps admin + frontend
  class-hdmoq-repository.php  rule resolution + validation -- see its docblock for the security design
  class-hdmoq-admin.php       hub tab, store settings, per-product override fields
  class-hdmoq-frontend.php    add-to-cart + woocommerce_check_cart_items enforcement, product-page hint
  class-hdmoq-hub.php         shared HDWebmobile admin hub (see the suite's other plugins)
```

## License

GPLv2 or later — https://www.gnu.org/licenses/gpl-2.0.html
