<?php
if (!defined('ABSPATH')) exit;

class Xenice_Member_Editor {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Register admin_post hooks
        add_action('admin_post_save_xenice_member', [$this, 'handle_save']);
        add_action('admin_post_delete_xenice_member', [$this, 'handle_delete']);
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
    }

    /**
     * Render the member editor form
     */
    public function render_editor() {
        // Nonce verification if editing existing member
        $user_id = 0;
        if (isset($_GET['user_id'])) {
            $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
            if (!wp_verify_nonce($nonce, 'edit_member')) {
                wp_die(esc_html__('Security check failed.', 'xenice-member'));
            }
            $user_id = intval($_GET['user_id']);
        }

        $user = $user_id ? get_user_by('ID', $user_id) : null;
        $levels = get_option('xenice_member_levels', []);

        $current_level  = $user_id ? get_user_meta($user_id, 'xm_level', true) : '';
        $current_expire = $user_id ? get_user_meta($user_id, 'xm_expire', true) : '';
        if ($current_expire === 'lifetime') {
            $current_expire = '';
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($user_id ? __('Edit Member', 'xenice-member') : __('Add Member', 'xenice-member')); ?></h1>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('save_member'); ?>
                <input type="hidden" name="action" value="save_xenice_member">
                <?php if ($user_id): ?>
                    <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                <?php else: ?>
                    <input type="hidden" name="add_membership" value="1">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th><label><?php echo esc_html__('User', 'xenice-member'); ?></label></th>
                        <td>
                            <?php if ($user_id): ?>
                                <strong><?php echo esc_html($user->user_login); ?></strong>
                            <?php else: ?>
                                <?php
                                wp_dropdown_users(array(
                                    'name' => 'user_id',
                                    'show_option_none' => esc_html__('Select User', 'xenice-member'),
                                    'selected' => 0,
                                    'class' => 'xm-user-select'
                                ));
                                ?>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th><label><?php echo esc_html__('Membership Level', 'xenice-member'); ?></label></th>
                        <td>
                            <select name="level" required>
                                <option value=""><?php echo esc_html__('Select Level', 'xenice-member'); ?></option>
                                <?php foreach ($levels as $level): ?>
                                    <option value="<?php echo esc_attr($level['id']); ?>"
                                        <?php selected($current_level, $level['id']); ?>>
                                        <?php echo esc_html($level['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <?php if ($user_id): ?>
                    <tr>
                        <th><label><?php echo esc_html__('Expiration Time', 'xenice-member'); ?></label></th>
                        <td>
                            <input type="datetime-local" name="expire"
                                   value="<?php echo $current_expire ? esc_attr(gmdate('Y-m-d\TH:i', $current_expire)) : ''; ?>">
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>

                <?php submit_button($user_id ? esc_html__('Update Member', 'xenice-member') : esc_html__('Add Member', 'xenice-member')); ?>
            </form>
        </div>
        <?php
    }
    
    public function admin_scripts($hook) {

        if (strpos($hook, 'xenice-member') === false) {
            return;
        }

        wp_enqueue_script(
            'xenice-member-editor-js',
            XENICE_MEMBER_PLUGIN_URL . 'assets/js/xenice-member-editor.js',
            array('jquery', 'select2'),
            '1.0.0',
            true
        );

        wp_localize_script('xenice-member-editor-js', 'xeniceMemberEditor', array(
            'select2Placeholder' => esc_html__('Search user…', 'xenice-member')
        ));
    
    }
    
    /**
     * Save member handler
     */
    public function handle_save() {
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
        if (!wp_verify_nonce($nonce, 'save_member')) {
            wp_die(esc_html__('Security check failed.', 'xenice-member'));
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $level   = isset($_POST['level']) ? intval($_POST['level']) : 0;
        $duration_value = xenice_member_get_duration_value($level);

        if ($duration_value === 'lifetime') {
            $expire = 'lifetime';
            update_user_meta($user_id, 'xm_level', $level);
            update_user_meta($user_id, 'xm_expire', $expire);
            wp_safe_redirect(admin_url('admin.php?page=xenice-member'));
            exit;
        }
        if (isset($_POST['add_membership'])) {
            $expire = get_user_meta($user_id, 'xm_expire', true);
            if($expire == 'lifetime'){
                wp_safe_redirect(admin_url('admin.php?page=xenice-member'));
                exit;
            }
            if(empty($expire) || $expire < time()){
                $expire = time();
            }
            $expire = $expire + $duration_value;
        } else {
            $expire = !empty($_POST['expire']) ? strtotime(sanitize_text_field(wp_unslash($_POST['expire']))) : 0;
        }
        
        update_user_meta($user_id, 'xm_level', $level);
        update_user_meta($user_id, 'xm_expire', $expire);

        wp_safe_redirect(admin_url('admin.php?page=xenice-member'));
        exit;
    }

    /**
     * Delete member handler
     */
    public function handle_delete() {
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (!wp_verify_nonce($nonce, 'delete_member')) {
            wp_die(esc_html__('Security check failed', 'xenice-member'));
        }

        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        if ($user_id) {
            delete_user_meta($user_id, 'xm_level');
            delete_user_meta($user_id, 'xm_expire');
        }

        wp_safe_redirect(admin_url('admin.php?page=xenice-member'));
        exit;
    }
}
