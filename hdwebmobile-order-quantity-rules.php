<?php

/**
 * Plugin Name: HDWebmobile Order Quantity Rules
 * Plugin URI: https://hdwebmobile.com/plugins/hdwebmobile-order-quantity-rules/
 * Description: Minimum, maximum, and step order-quantity rules -- enforced server-side on every checkout path, not just in the cart page's own JS.
 * Version: 1.0.0
 * Author: htrxuan - Han Tran
 * Author URI: https://hdwebmobile.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hdwebmobile-order-quantity-rules
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * Requires at least: 6.9
 */

namespace htrxuan\hdmoq;

if (!defined('ABSPATH')) {
    exit;
}

define('HDMOQ_VERSION', '1.0.0');
define('HDMOQ_PLUGIN_FILE', __FILE__);
define('HDMOQ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HDMOQ_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once HDMOQ_PLUGIN_DIR . 'includes/class-hdmoq-activator.php';

register_activation_hook(__FILE__, array(HDMOQ_Activator::class, 'activate'));

add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', HDMOQ_PLUGIN_FILE, true);
    }
});

add_action('plugins_loaded', function () {
    require_once HDMOQ_PLUGIN_DIR . 'includes/class-hdmoq-core.php';
    HDMOQ_Core::get_instance();
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $donate_link = '<a href="https://paypal.me/htrxuan/20" target="_blank" rel="noopener noreferrer">' . esc_html__('Donate', 'hdwebmobile-order-quantity-rules') . '</a>';
    array_unshift($links, $donate_link);
    return $links;
});
