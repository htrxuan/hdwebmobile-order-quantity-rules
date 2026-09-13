<?php

namespace htrxuan\hdmoq;

if (!defined('ABSPATH')) {
    exit;
}

class HDMOQ_Admin
{

    const NONCE_PRODUCT = 'hdmoq_save_product';

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        require_once HDMOQ_PLUGIN_DIR . 'includes/class-hdmoq-hub.php';
        add_filter('hdwebmobile_hub_tabs', array($this, 'register_hub_tabs'));
        add_action('admin_init', array($this, 'register_settings'));

        add_action('woocommerce_product_options_general_product_data', array($this, 'render_product_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_fields'));
    }

    public function register_hub_tabs($tabs)
    {
        $tabs['order-quantity-rules'] = array(
            'label'  => __('Quantity Rules', 'hdwebmobile-order-quantity-rules'),
            'order'  => 151,
            'render' => array($this, 'render_page'),
        );
        return $tabs;
    }

    public function register_settings()
    {
        register_setting('hdmoq_settings_group', 'hdmoq_settings', array('htrxuan\\hdmoq\\HDMOQ_Repository', 'save_settings'));
    }

    public function render_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to do this.', 'hdwebmobile-order-quantity-rules'));
        }

        $settings = HDMOQ_Repository::get_settings();
        ?>
        <p><?php esc_html_e('Set a store-wide minimum, maximum, and order-multiple (step) quantity. Override any of these for an individual product on its own Inventory tab. Rules are enforced on the cart page, at checkout, and in the block-based checkout alike -- not only in this admin form.', 'hdwebmobile-order-quantity-rules'); ?></p>

        <form method="post" action="options.php">
            <?php settings_fields('hdmoq_settings_group'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="hdmoq_min_qty"><?php esc_html_e('Default minimum quantity', 'hdwebmobile-order-quantity-rules'); ?></label></th>
                    <td><input type="number" id="hdmoq_min_qty" name="hdmoq_settings[min_qty]" min="1" step="1" value="<?php echo esc_attr($settings['min_qty']); ?>" style="width:100px;" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="hdmoq_max_qty"><?php esc_html_e('Default maximum quantity', 'hdwebmobile-order-quantity-rules'); ?></label></th>
                    <td>
                        <input type="number" id="hdmoq_max_qty" name="hdmoq_settings[max_qty]" min="0" step="1" value="<?php echo esc_attr($settings['max_qty']); ?>" style="width:100px;" />
                        <p class="description"><?php esc_html_e('0 means no maximum.', 'hdwebmobile-order-quantity-rules'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="hdmoq_step"><?php esc_html_e('Default order multiple (step)', 'hdwebmobile-order-quantity-rules'); ?></label></th>
                    <td>
                        <input type="number" id="hdmoq_step" name="hdmoq_settings[step]" min="1" step="1" value="<?php echo esc_attr($settings['step']); ?>" style="width:100px;" />
                        <p class="description"><?php esc_html_e('E.g. 6 means quantities must be 6, 12, 18...', 'hdwebmobile-order-quantity-rules'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save Settings', 'hdwebmobile-order-quantity-rules')); ?>
        </form>
        <?php
    }

    public function render_product_fields()
    {
        global $post;
        $product_id = $post->ID;
        $min  = get_post_meta($product_id, '_hdmoq_min', true);
        $max  = get_post_meta($product_id, '_hdmoq_max', true);
        $step = get_post_meta($product_id, '_hdmoq_step', true);

        echo '<div class="options_group">';
        wp_nonce_field(self::NONCE_PRODUCT, 'hdmoq_product_nonce');
        woocommerce_wp_text_input(array(
            'id'          => '_hdmoq_min',
            'label'       => __('Min order qty', 'hdwebmobile-order-quantity-rules'),
            'description' => __('Blank uses the store default.', 'hdwebmobile-order-quantity-rules'),
            'desc_tip'    => true,
            'type'        => 'number',
            'custom_attributes' => array('min' => '1', 'step' => '1'),
            'value'       => $min,
        ));
        woocommerce_wp_text_input(array(
            'id'          => '_hdmoq_max',
            'label'       => __('Max order qty', 'hdwebmobile-order-quantity-rules'),
            'description' => __('Blank uses the store default.', 'hdwebmobile-order-quantity-rules'),
            'desc_tip'    => true,
            'type'        => 'number',
            'custom_attributes' => array('min' => '0', 'step' => '1'),
            'value'       => $max,
        ));
        woocommerce_wp_text_input(array(
            'id'          => '_hdmoq_step',
            'label'       => __('Order multiple (step)', 'hdwebmobile-order-quantity-rules'),
            'description' => __('Blank uses the store default.', 'hdwebmobile-order-quantity-rules'),
            'desc_tip'    => true,
            'type'        => 'number',
            'custom_attributes' => array('min' => '1', 'step' => '1'),
            'value'       => $step,
        ));
        echo '</div>';
    }

    public function save_product_fields($product_id)
    {
        if (!isset($_POST['hdmoq_product_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['hdmoq_product_nonce'])), self::NONCE_PRODUCT)) {
            return;
        }
        if (!current_user_can('edit_product', $product_id)) {
            return;
        }

        HDMOQ_Repository::save_product_overrides(
            $product_id,
            isset($_POST['_hdmoq_min']) ? sanitize_text_field(wp_unslash($_POST['_hdmoq_min'])) : '',
            isset($_POST['_hdmoq_max']) ? sanitize_text_field(wp_unslash($_POST['_hdmoq_max'])) : '',
            isset($_POST['_hdmoq_step']) ? sanitize_text_field(wp_unslash($_POST['_hdmoq_step'])) : ''
        );
    }
}
