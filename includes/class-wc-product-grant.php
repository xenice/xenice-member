<?php
if (!defined('ABSPATH')) exit;

class Xenice_Member_WC_Product_Grant {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // 确保 WooCommerce 已激活
        if (!class_exists('WooCommerce')) {
            return;
        }

        // 添加产品编辑字段
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_membership_level_field']);
        add_action('woocommerce_process_product_meta', [$this, 'save_membership_level_field']);

        // 监听订单完成事件
        add_action('woocommerce_order_status_completed', [$this, 'grant_membership_on_purchase']);
    }

    /**
     * 在产品编辑页添加“授权会员等级”下拉框
     */
    public function add_membership_level_field() {
        global $post;

        $selected_level = get_post_meta($post->ID, '_xenice_grant_member_level', true);
        $levels = get_option('xenice_member_levels', []);

        echo '<div class="options_group">';

        woocommerce_wp_select([
            'id'          => '_xenice_grant_member_level',
            'label'       => __('授权会员等级', 'xenice-member'),
            'description' => __('用户购买此产品并完成订单后，将自动获得所选会员等级。', 'xenice-member'),
            'options'     => array_merge(
                ['' => __('— 不授权 —', 'xenice-member')],
                wp_list_pluck($levels, 'name', 'id')
            ),
            'value'       => $selected_level,
            'desc_tip'    => true,
        ]);

        echo '</div>';
    }

    /**
     * 保存产品上设置的会员等级
     */
    public function save_membership_level_field($post_id) {
        $level = isset($_POST['_xenice_grant_member_level']) 
            ? sanitize_text_field(wp_unslash($_POST['_xenice_grant_member_level'])) 
            : '';
        update_post_meta($post_id, '_xenice_grant_member_level', $level);
    }

    /**
     * 订单完成后自动授予会员权限
     */
    public function grant_membership_on_purchase($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $user_id = $order->get_customer_id();
        if (!$user_id) return; // 跳过游客订单

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $level_id = get_post_meta($product_id, '_xenice_grant_member_level', true);

            if (empty($level_id)) continue;

            $levels = get_option('xenice_member_levels', []);
            $level_config = null;

            foreach ($levels as $level) {
                if ((string)$level['id'] === (string)$level_id) {
                    $level_config = $level;
                    break;
                }
            }

            if (!$level_config) continue;

            $duration = isset($level_config['duration']) ? (int)$level_config['duration'] : 0;
            $now = time();

            if ($duration <= 0) {
                // 终身会员
                update_user_meta($user_id, 'xm_level', $level_id);
                update_user_meta($user_id, 'xm_expire', 'lifetime');
            } else {
                $existing_expire = get_user_meta($user_id, 'xm_expire', true);

                if ($existing_expire && $existing_expire !== 'lifetime' && $existing_expire > $now) {
                    $new_expire = $existing_expire + $duration * DAY_IN_SECONDS;
                } else {
                    $new_expire = $now + $duration * DAY_IN_SECONDS;
                }

                update_user_meta($user_id, 'xm_level', $level_id);
                update_user_meta($user_id, 'xm_expire', $new_expire);
            }
        }
    }
}
