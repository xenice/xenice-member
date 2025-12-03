<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Used for safe page routing only.
$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
if ( in_array( $action, [ 'add', 'edit' ], true ) ) {
    /**
     * Instantiate and process member editor actions.
     */
    $xenice_member_editor = Xenice_Member_Editor::get_instance();
    $xenice_member_editor->render_editor();
    return;
}


/**
 * Prepare member list table.
 */
$xenice_member_table = new Xenice_Member_List_Table();
$xenice_member_table->prepare_items();


?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e( 'Member List', 'xenice-member' ); ?>
    </h1>

    <a href="<?php echo esc_url( admin_url( 'admin.php?page=xenice-member&action=add' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'Add Member', 'xenice-member' ); ?>
    </a>

    <hr class="wp-header-end">

    <form method="get">
        <input type="hidden" name="page" value="xenice-member" />
        <?php $xenice_member_table->display(); ?>
    </form>
</div>
