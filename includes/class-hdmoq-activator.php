<?php

namespace htrxuan\hdmoq;

if (!defined('ABSPATH')) {
    exit;
}

class HDMOQ_Activator
{

    public static function activate()
    {
        if (!self::is_woocommerce_active()) {
            deactivate_plugins(plugin_basename(HDMOQ_PLUGIN_FILE));
            set_transient('hdmoq_wc_missing_notice', true, 30);
            return;
        }

        // TODO: if this plugin needs its own DB table, add a maybe_upgrade_db() here that
        // dbDelta()s a CREATE TABLE against a *_Repository::get_schema_sql() and stamps a
        // 'hdmoq_db_version' option -- see hdwebmobile-wholesale-pricing or
        // hdwebmobile-booking-appointments's activator for the exact pattern.
    }

    public static function is_woocommerce_active()
    {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active('woocommerce/woocommerce.php') || class_exists('WooCommerce');
    }
}
