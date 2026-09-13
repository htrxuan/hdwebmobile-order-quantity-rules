<?php

namespace htrxuan\hdmoq;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resolves the effective min/max/step quantity rule for a product, and is the single place
 * that actually validates a requested quantity against it.
 *
 * Several competing "minimum/maximum order quantity" plugins enforced their rules only in the
 * cart page's own JavaScript (disabling the quantity field's +/- buttons, greying out an
 * "Update Cart" button) and in a plain `woocommerce_add_to_cart_validation` check -- but never
 * re-validated at the point an order is actually allowed to proceed. That left the classic
 * "skip the JS" bypass (CWE-602, Client-Side Enforcement of Server-Side Security) wide open:
 * add the product with a valid quantity, then edit the cart-item quantity directly via a raw
 * `update_cart_action` POST or the Store API's own cart-update endpoint, and the rule was
 * never checked again.
 *
 * This plugin closes that by hooking `woocommerce_check_cart_items` (see HDMOQ_Frontend) --
 * the one authoritative re-validation point WooCommerce itself calls on every cart page load
 * AND at both the classic checkout AND the Store API's own cart/checkout routes (confirmed by
 * reading `WC_Cart::check_cart_items()`'s registration and the Store API's own
 * `CartController::validate_cart()`, which fires the exact same `woocommerce_check_cart_items`
 * action even though it unhooks WooCommerce's own default handlers for that call). A quantity
 * that only ever got into the cart by skipping the JS is still caught there, every time.
 */
class HDMOQ_Repository
{
    public static function get_settings()
    {
        $defaults = array('min_qty' => 1, 'max_qty' => 0, 'step' => 1);
        $settings = get_option('hdmoq_settings', array());
        return wp_parse_args(is_array($settings) ? $settings : array(), $defaults);
    }

    public static function save_settings($input)
    {
        $settings = array(
            'min_qty' => max(1, isset($input['min_qty']) ? absint($input['min_qty']) : 1),
            'max_qty' => isset($input['max_qty']) ? absint($input['max_qty']) : 0,
            'step'    => max(1, isset($input['step']) ? absint($input['step']) : 1),
        );
        update_option('hdmoq_settings', $settings);
        return $settings;
    }

    /**
     * The effective rule for one product: its own override meta if set, otherwise the
     * store-wide default. A blank per-product field always means "use the default", never 0.
     */
    public static function get_rule_for_product($product_id)
    {
        $defaults = self::get_settings();

        $min  = get_post_meta($product_id, '_hdmoq_min', true);
        $max  = get_post_meta($product_id, '_hdmoq_max', true);
        $step = get_post_meta($product_id, '_hdmoq_step', true);

        return array(
            'min_qty' => ('' !== $min) ? max(1, absint($min)) : $defaults['min_qty'],
            'max_qty' => ('' !== $max) ? absint($max) : $defaults['max_qty'],
            'step'    => ('' !== $step) ? max(1, absint($step)) : $defaults['step'],
        );
    }

    public static function save_product_overrides($product_id, $min, $max, $step)
    {
        self::save_override_field($product_id, '_hdmoq_min', $min);
        self::save_override_field($product_id, '_hdmoq_max', $max);
        self::save_override_field($product_id, '_hdmoq_step', $step);
    }

    private static function save_override_field($product_id, $key, $value)
    {
        $value = trim((string) $value);
        if ('' === $value) {
            delete_post_meta($product_id, $key);
            return;
        }
        update_post_meta($product_id, $key, absint($value));
    }

    /**
     * The one place a quantity is actually checked against a rule. Returns true, or a
     * WP_Error carrying a shopper-facing message -- never silently clamps or rounds the
     * quantity, so a shopper always sees exactly why their requested amount was rejected.
     */
    public static function validate_quantity($product_id, $quantity)
    {
        $quantity = (int) $quantity;
        $rule     = self::get_rule_for_product($product_id);

        if ($quantity < $rule['min_qty']) {
            return new \WP_Error('hdmoq_below_min', sprintf(
                /* translators: %d: minimum order quantity */
                __('The minimum quantity for this product is %d.', 'hdwebmobile-order-quantity-rules'),
                $rule['min_qty']
            ));
        }

        if ($rule['max_qty'] > 0 && $quantity > $rule['max_qty']) {
            return new \WP_Error('hdmoq_above_max', sprintf(
                /* translators: %d: maximum order quantity */
                __('The maximum quantity for this product is %d.', 'hdwebmobile-order-quantity-rules'),
                $rule['max_qty']
            ));
        }

        if ($rule['step'] > 1 && 0 !== $quantity % $rule['step']) {
            return new \WP_Error('hdmoq_bad_step', sprintf(
                /* translators: %d: required multiple */
                __('This product must be ordered in multiples of %d.', 'hdwebmobile-order-quantity-rules'),
                $rule['step']
            ));
        }

        return true;
    }
}
