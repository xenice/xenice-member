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

/**
 * Deleted message handling.
 * phpcs:disable WordPress.Security.NonceVerification.Recommended
 */
$xenice_member_deleted = 0;

if ( isset( $_REQUEST['deleted'] ) ) {
    $xenice_member_deleted_raw = wp_unslash( $_REQUEST['deleted'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $xenice_member_deleted     = absint( $xenice_member_deleted_raw );
}
/** phpcs:enable WordPress.Security.NonceVerification.Recommended */
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e( 'Member List', 'xenice-member' ); ?>
    </h1>

    <a href="<?php echo esc_url( admin_url( 'admin.php?page=xenice-member&action=add' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'Add Member', 'xenice-member' ); ?>
    </a>

    <hr class="wp-header-end">

    <?php if ( $xenice_member_deleted > 0 ) : ?>
        <div id="message" class="updated notice is-dismissible">
            <p>
                <?php
                    $xenice_member_message = sprintf(
                        /* translators: %d: number of memberships deleted */
                        _n(
                            '%d membership deleted.',
                            '%d memberships deleted.',
                            $xenice_member_deleted,
                            'xenice-member'
                        ),
                        number_format_i18n( $xenice_member_deleted )
                    );
                    
                    echo esc_html( $xenice_member_message );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <form method="get">
        <input type="hidden" name="page" value="xenice-member" />
        <?php $xenice_member_table->display(); ?>
    </form>
</div>
