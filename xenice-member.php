<?php
/**
 * Plugin Name: Xenice Member
 * Plugin URI: https://www.xenice.com/xenice-member
 * Description: A comprehensive membership management plugin for WordPress.
 * Version: 1.0.0
 * Author: xenice
 * Author URI: https://www.xenice.com/
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: xenice-member
 * Domain Path: /languages
 */


// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('XENICE_MEMBER_VERSION', '1.0.0');
define('XENICE_MEMBER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('XENICE_MEMBER_PLUGIN_DIR', plugin_dir_path(__FILE__));

require __DIR__ . '/functions.php';

// Initialize plugin
class Xenice_Member {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));

        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {

        // Load admin classes
        if (is_admin()) {
            require_once XENICE_MEMBER_PLUGIN_DIR . 'includes/class-member-list-table.php';
            require_once XENICE_MEMBER_PLUGIN_DIR . 'includes/class-member-editor.php';
            require_once XENICE_MEMBER_PLUGIN_DIR . 'includes/class-level-list-table.php';
            require_once XENICE_MEMBER_PLUGIN_DIR . 'includes/class-level-editor.php';
            Xenice_Member_Editor::get_instance();
            Xenice_Level_Editor::get_instance();
            
            // Extension class
            require_once XENICE_MEMBER_PLUGIN_DIR . 'includes/class-level-duration.php';
            new Xenice_Level_Duration;
        }

        require_once XENICE_MEMBER_PLUGIN_DIR . 'includes/class-wc-free-download.php';
        require_once XENICE_MEMBER_PLUGIN_DIR . 'includes/class-wc-product-grant.php';
        new Xenice_WC_Free_Download;
        Xenice_Member_WC_Product_Grant::get_instance();
    }

    public function admin_menu() {
        add_menu_page(
            __('Members', 'xenice-member'),
            __('Members', 'xenice-member'),
            'manage_options',
            'xenice-member',
            array($this, 'member_list_page'),
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'xenice-member',
            __('Member List', 'xenice-member'),
            __('Member List', 'xenice-member'),
            'manage_options',
            'xenice-member',
            array($this, 'member_list_page')
        );

        add_submenu_page(
            'xenice-member',
            __('Membership Levels', 'xenice-member'),
            __('Membership Levels', 'xenice-member'),
            'manage_options',
            'xenice-member-levels',
            array($this, 'level_list_page')
        );
    }

    public function member_list_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'xenice-member'));
        }

        require_once XENICE_MEMBER_PLUGIN_DIR . 'templates/member-list.php';
    }

    public function level_list_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'xenice-member'));
        }

        require_once XENICE_MEMBER_PLUGIN_DIR . 'templates/level-list.php';
    }

    public function admin_scripts($hook) {
        if (strpos($hook, 'xenice-member') === false) {
            return;
        }

        wp_enqueue_style('select2-css', XENICE_MEMBER_PLUGIN_URL . 'assets/select2/select2.min.css', array(), '4.1.0');
        wp_enqueue_script('select2-js', XENICE_MEMBER_PLUGIN_URL . 'assets/select2/select2.min.js', array('jquery'), '4.1.0', true);

    }

    public function activate() {
        // Create default membership levels
        $default_levels = array(
            array(
                'id' => 1,
                'name' => __('Lifetime Member', 'xenice-member'),
                'duration' => '0',
                'permissions' => array()
            ),
            array(
                'id' => 2,
                'name' => __('Annual Member', 'xenice-member'),
                'duration' => '365',
                'permissions' => array()
            ),
            array(
                'id' => 3,
                'name' => __('Monthly Member', 'xenice-member'),
                'duration' => '30',
                'permissions' => array()
            )
        );

        if (!get_option('xenice_member_levels')) {
            update_option('xenice_member_levels', $default_levels);
        }
    }
}

// Helper function
function xenice_member() {
    return Xenice_Member::get_instance();
}

// Start plugin
xenice_member();
