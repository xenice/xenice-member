<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Used for safe page routing only.
$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';

if ( in_array( $action, [ 'add', 'edit' ], true ) ) {
    $xenice_member_level_editor = Xenice_Level_Editor::get_instance();
    $xenice_member_level_editor->render_editor();
    return;
}

/**
 * Prepare table list.
 */
$xenice_member_level_table = new Xenice_Level_List_Table();
$xenice_member_level_table->prepare_items();

/**
 * Deleted message handling.
 * phpcs:disable WordPress.Security.NonceVerification.Recommended
 */
$xenice_member_deleted = 0;

if ( isset( $_REQUEST['deleted'] ) ) {
    $xenice_member_deleted_raw = wp_unslash( $_REQUEST['deleted'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $xenice_member_deleted     = absint( $xenice_member_deleted_raw );
}
/**
 * phpcs:enable WordPress.Security.NonceVerification.Recommended
 */
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e( 'Membership Levels', 'xenice-member' ); ?>
    </h1>

    <a href="<?php echo esc_url( admin_url( 'admin.php?page=xenice-member-levels&action=add' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'Add Level', 'xenice-member' ); ?>
    </a>

    <hr class="wp-header-end">

    <?php if ( $xenice_member_deleted > 0 ) : ?>
        <div id="message" class="updated notice is-dismissible">
            <p>
                <?php
                    $xenice_member_message = sprintf(
                        /* translators: %d: number of levels deleted */
                        _n(
                            '%d level deleted.',
                            '%d levels deleted.',
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
        <input type="hidden" name="page" value="xenice-member-levels" />
        <?php $xenice_member_level_table->display(); ?>
    </form>
</div>
