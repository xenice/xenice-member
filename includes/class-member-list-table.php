<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Xenice_Member_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(array(
            'singular' => 'member',
            'plural'   => 'members',
            'ajax'     => false
        ));
    }

    public function get_columns() {
        return array(
            'cb'            => '<input type="checkbox" />',
            'username'      => esc_html__('Username', 'xenice-member'),
            'email'         => esc_html__('Email', 'xenice-member'),
            'level'         => esc_html__('Membership Level', 'xenice-member'),
            'expire'        => esc_html__('Expiration Time', 'xenice-member'),
            'status'        => esc_html__('Status', 'xenice-member'),
            'register_date' => esc_html__('Registration Date', 'xenice-member')
        );
    }

    protected function get_sortable_columns() {
        return array(
            'username'      => array('login', false),
            'email'         => array('email', false),
            'level'         => array('xm_level', false),
            'expire'        => array('xm_expire', false),
            'status'        => array('xm_expire', false),
            'register_date' => array('registered', true)
        );
    }

    protected function column_default($item, $column_name) {
        return isset($item->$column_name) ? esc_html($item->$column_name) : '';
    }

    protected function column_cb($item) {
        return sprintf('<input type="checkbox" name="member[]" value="%d" />', intval($item->ID));
    }

    protected function column_username($item) {
        $edit_url = esc_url(admin_url('admin.php?page=xenice-member&action=edit&user_id=' . intval($item->ID)));
        $delete_url = wp_nonce_url(
            admin_url('admin-post.php?action=delete_xenice_member&user_id=' . intval($item->ID)),
            'delete_member'
        );

        $actions = array(
            'edit' => sprintf('<a href="%s">%s</a>', $edit_url, esc_html__('Edit Member', 'xenice-member')),
            'delete' => sprintf(
                '<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
                esc_url($delete_url),
                esc_js(__('Are you sure you want to delete this member?', 'xenice-member')),
                esc_html__('Delete Membership', 'xenice-member')
            )
        );

        return sprintf('<strong><a href="%s">%s</a></strong>%s',
            $edit_url,
            esc_html($item->user_login),
            $this->row_actions($actions)
        );
    }

    protected function column_email($item) {
        return esc_html($item->user_email);
    }

    protected function column_level($item) {
        $level_id = get_user_meta($item->ID, 'xm_level', true);
        $levels   = get_option('xenice_member_levels', array());

        foreach ($levels as $level) {
            if ($level['id'] == $level_id) {
                return esc_html($level['name']);
            }
        }

        return esc_html__('None', 'xenice-member');
    }

    protected function column_expire($item) {
        $expire = get_user_meta($item->ID, 'xm_expire', true);

        if ($expire === 'lifetime') {
            return esc_html__('Lifetime', 'xenice-member');
        } elseif ($expire && $expire > time()) {
            return esc_html(gmdate('Y-m-d H:i:s', intval($expire)));
        } elseif ($expire && $expire <= time()) {
            return '<span style="color:red;">' . esc_html__('Expired', 'xenice-member') . '</span>';
        } else {
            return esc_html__('None', 'xenice-member');
        }
    }

    protected function column_status($item) {
        $expire = get_user_meta($item->ID, 'xm_expire', true);

        if ($expire === 'lifetime' || ($expire && $expire > time())) {
            return '<span style="color:green;">' . esc_html__('Active', 'xenice-member') . '</span>';
        } else {
            return '<span style="color:red;">' . esc_html__('Inactive', 'xenice-member') . '</span>';
        }
    }

    protected function column_register_date($item) {
        return esc_html(gmdate('Y-m-d H:i:s', strtotime($item->user_registered)));
    }

    public function prepare_items() {
        $this->process_bulk_action();

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $per_page     = 20;
        $current_page = $this->get_pagenum();
        $offset       = ($current_page - 1) * $per_page;

        // Sanitize GET inputs
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $orderby = isset($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : 'registered';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order   = isset($_GET['order']) ? sanitize_text_field(wp_unslash($_GET['order'])) : 'DESC';

        $args = array(
            'number'     => $per_page,
            'offset'     => $offset,
            'orderby'    => $orderby,
            'order'      => $order,
            'meta_query' => array(
                array(
                    'key'     => 'xm_level',
                    'value'   => '',
                    'compare' => '!='
                )
            )
        );

        // Handle meta sorting
        if (in_array($orderby, array('xm_level', 'xm_expire'))) {
            $args['meta_key'] = $orderby;
            $args['orderby']  = 'meta_value_num';
        }

        $users = get_users($args);

        $total_users = count(get_users(array(
            'fields' => 'ID',
            'meta_query' => array(
                array(
                    'key'     => 'xm_level',
                    'value'   => '',
                    'compare' => '!='
                )
            )
        )));

        $this->items = $users;

        $this->set_pagination_args(array(
            'total_items' => $total_users,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_users / $per_page)
        ));
    }

    protected function get_bulk_actions() {
        return array(
            'delete' => esc_html__('Delete', 'xenice-member')
        );
    }

    public function process_bulk_action() {
        if ('delete' !== $this->current_action()) {
            return;
        }

        $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($nonce, 'bulk-members')) {
            wp_die(esc_html__('Security check failed', 'xenice-member'));
        }

        if (!isset($_REQUEST['member']) || empty($_REQUEST['member'])) {
            wp_die(esc_html__('No members selected', 'xenice-member'));
        }

        $user_ids = array_map('intval', $_REQUEST['member']);
        $deleted_count = 0;

        foreach ($user_ids as $user_id) {
            if (delete_user_meta($user_id, 'xm_level') && delete_user_meta($user_id, 'xm_expire')) {
                $deleted_count++;
            }
        }

        wp_safe_redirect(add_query_arg('deleted', $deleted_count, admin_url('admin.php?page=xenice-member')));
        exit;
    }
}
