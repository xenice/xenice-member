<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Xenice_Level_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( array(
            'singular' => 'level',
            'plural'   => 'levels',
            'ajax'     => false,
        ) );
    }

    public function get_columns() {
        return array(
            'cb'          => '<input type="checkbox" />',
            'name'        => esc_html__( 'Level Name', 'xenice-member' ),
            'duration'    => esc_html__( 'Duration', 'xenice-member' ),
            'permissions' => esc_html__( 'Permissions', 'xenice-member' ),
            'id'          => esc_html__( 'ID', 'xenice-member' ),
        );
    }

    protected function get_sortable_columns() {
        return array(
            'name' => array( 'name', false ),
        );
    }

    protected function column_default( $item, $column_name ) {
        return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
    }

    protected function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="xenice_member_level[]" value="%s" />', esc_attr( $item['id'] ) );
    }

    protected function column_name( $item ) {
        $edit_url = admin_url( 'admin.php?page=xenice-member-levels&action=edit&id=' . $item['id'] );
        $delete_url = wp_nonce_url(
            admin_url( 'admin-post.php?action=delete_xenice_level&id=' . $item['id'] ),
            'delete_level'
        );

        $actions = array(
            'edit' => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'Edit', 'xenice-member' ) ),
            'delete' => sprintf(
                '<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
                esc_url( $delete_url ),
                esc_js( esc_html__( 'Are you sure you want to delete this level?', 'xenice-member' ) ),
                esc_html__( 'Delete', 'xenice-member' )
            ),
        );

        return sprintf( '<strong>%s</strong>%s', esc_html( $item['name'] ), $this->row_actions( $actions ) );
    }

    protected function column_duration( $item ) {
        $xenice_member_map = apply_filters( 'xenice_member_duration_options', array() );
        $value = isset( $item['duration'] ) ? $item['duration'] : '';
        return isset( $xenice_member_map[ $value ] ) ? esc_html( $xenice_member_map[ $value ] ) : esc_html( $value );
    }

    protected function column_permissions( $item ) {
        $permissions = isset( $item['permissions'] ) ? $item['permissions'] : array();
        $permission_names = apply_filters( 'xenice_member_permission_options', array() );

        $names = array();
        foreach ( $permissions as $permission ) {
            $names[] = isset( $permission_names[ $permission ] )
                ? esc_html( $permission_names[ $permission ] )
                : esc_html( $permission );
        }

        return implode( ', ', $names );
    }

    public function prepare_items() {
        $this->process_bulk_action();
        $columns = $this->get_columns();
        $hidden = array( 'id' );
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );

        $xenice_member_levels = get_option( 'xenice_member_levels', array() );

        // Sorting
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $xenice_member_orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'id';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $xenice_member_order   = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'ASC';

        usort( $xenice_member_levels, function( $a, $b ) use ( $xenice_member_orderby, $xenice_member_order ) {
            return ( $xenice_member_order === 'ASC' )
                ? $a[ $xenice_member_orderby ] <=> $b[ $xenice_member_orderby ]
                : $b[ $xenice_member_orderby ] <=> $a[ $xenice_member_orderby ];
        } );

        $this->items = $xenice_member_levels;
    }

    protected function get_bulk_actions() {
        return array(
            'delete' => esc_html__( 'Delete', 'xenice-member' ),
        );
    }

    public function process_bulk_action() {
        if ( 'delete' !== $this->current_action() ) {
            return;
        }

        // Step 1: check if nonce exists
        if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
            wp_die( esc_html__( 'Security check failed', 'xenice-member' ) );
        }
        
        // Step 2: unslash + sanitize
        $xenice_member_nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );
        
        // Step 3: verify nonce
        if ( ! wp_verify_nonce( $xenice_member_nonce, 'bulk-levels' ) ) {
            wp_die( esc_html__( 'Security check failed', 'xenice-member' ) );
        }

        if ( ! isset( $_REQUEST['xenice_member_level'] ) || empty( $_REQUEST['xenice_member_level'] ) ) {
            wp_die( esc_html__( 'No levels selected', 'xenice-member' ) );
        }

        $xenice_member_level_ids = array_map( 'intval', wp_unslash( $_REQUEST['xenice_member_level'] ) );
        $xenice_member_levels = get_option( 'xenice_member_levels', array() );
        $xenice_member_deleted_count = 0;

        $xenice_member_updated_levels = array_filter( $xenice_member_levels, function( $level ) use ( $xenice_member_level_ids, &$xenice_member_deleted_count ) {
            if ( in_array( $level['id'], $xenice_member_level_ids, true ) ) {
                $xenice_member_deleted_count++;
                return false;
            }
            return true;
        } );

        update_option( 'xenice_member_levels', $xenice_member_updated_levels );

        // Safe redirect
        wp_safe_redirect( admin_url( 'admin.php?page=xenice-member-levels' ) );
        exit;
    }
}
