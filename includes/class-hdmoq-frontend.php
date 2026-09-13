<?php

namespace htrxuan\hdmoq;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enforces quantity rules at add-to-cart (for immediate feedback) and again, authoritatively,
 * at woocommerce_check_cart_items -- the hook that fires for the cart page, classic checkout,
 * AND the Store API's block cart/checkout. See HDMOQ_Repository's docblock for why the second
 * hook is what actually closes the CVE class this plugin is built against.
 */
final class HDMOQ_Frontend
{

    private static $instance = null;
    private static $hint_done = false;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_add_to_cart'), 10, 3);
        add_action('woocommerce_check_cart_items', array($this, 'validate_cart_contents'));
        add_action('woocommerce_before_add_to_cart_button', array($this, 'render_hint'));
        add_filter('render_block', array($this, 'block_fallback'), 10, 2);
        add_filter('woocommerce_quantity_input_args', array($this, 'quantity_input_args'), 10, 2);
    }

    public function validate_add_to_cart($passed, $product_id, $quantity)
    {
        $result = HDMOQ_Repository::validate_quantity($product_id, $quantity);
        if (is_wp_error($result)) {
            wc_add_notice($result->get_error_message(), 'error');
            return false;
        }
        return $passed;
    }

    /**
     * The authoritative check: sums each product's quantity across every matching cart line
     * (a product can appear more than once, e.g. different variations) and validates the
     * total, on every request that reaches this hook -- regardless of how the quantity got
     * into the cart.
     */
    public function validate_cart_contents()
    {
        if (!WC()->cart) {
            return;
        }

        $totals_by_product = array();
        foreach (WC()->cart->get_cart() as $item) {
            $pid = (int) $item['product_id'];
            $totals_by_product[$pid] = ($totals_by_product[$pid] ?? 0) + (int) $item['quantity'];
        }

        foreach ($totals_by_product as $product_id => $quantity) {
            $result = HDMOQ_Repository::validate_quantity($product_id, $quantity);
            if (is_wp_error($result)) {
                $product = wc_get_product($product_id);
                $name    = $product ? $product->get_name() : '#' . $product_id;
                wc_add_notice(sprintf('%s: %s', esc_html($name), esc_html($result->get_error_message())), 'error');
            }
        }
    }

    /**
     * Cosmetic only -- sets the quantity input's own min/max/step attributes so a shopper
     * usually never hits the error at all. This is deliberately NOT the enforcement itself
     * (see validate_add_to_cart()/validate_cart_contents() above), since a shopper can always
     * submit a value the browser's own number-input constraints didn't stop.
     */
    public function quantity_input_args($args, $product)
    {
        $rule = HDMOQ_Repository::get_rule_for_product($product->get_id());
        $args['min_value']  = $rule['min_qty'];
        $args['step']       = $rule['step'];
        $args['input_value'] = max($rule['min_qty'], (int) ($args['input_value'] ?? $rule['min_qty']));
        if ($rule['max_qty'] > 0) {
            $args['max_value'] = $rule['max_qty'];
        }
        return $args;
    }

    public function render_hint()
    {
        global $product;
        if (self::$hint_done || !$product instanceof \WC_Product) {
            return;
        }
        self::$hint_done = true;
        $this->output_hint($product);
    }

    public function block_fallback($block_content, $block)
    {
        $name = is_array($block) && isset($block['blockName']) ? $block['blockName'] : '';
        if ('woocommerce/add-to-cart-form' !== $name || !is_singular('product')) {
            return $block_content;
        }
        if (self::$hint_done) {
            return $block_content;
        }
        global $product;
        if (!$product instanceof \WC_Product) {
            return $block_content;
        }
        self::$hint_done = true;
        ob_start();
        $this->output_hint($product);
        return $block_content . ob_get_clean();
    }

    private function output_hint($product)
    {
        $rule = HDMOQ_Repository::get_rule_for_product($product->get_id());
        $parts = array();
        if ($rule['min_qty'] > 1) {
            $parts[] = sprintf(
                /* translators: %d: minimum quantity */
                __('Minimum order: %d', 'hdwebmobile-order-quantity-rules'),
                $rule['min_qty']
            );
        }
        if ($rule['max_qty'] > 0) {
            $parts[] = sprintf(
                /* translators: %d: maximum quantity */
                __('Maximum order: %d', 'hdwebmobile-order-quantity-rules'),
                $rule['max_qty']
            );
        }
        if ($rule['step'] > 1) {
            $parts[] = sprintf(
                /* translators: %d: required multiple */
                __('Sold in multiples of %d', 'hdwebmobile-order-quantity-rules'),
                $rule['step']
            );
        }
        if (empty($parts)) {
            return;
        }
        echo '<p class="hdmoq-hint">' . esc_html(implode(' -- ', $parts)) . '</p>';
    }
}
