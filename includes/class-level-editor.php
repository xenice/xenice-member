<?php
if (!defined('ABSPATH')) {
    exit;
}

class Xenice_Level_Editor {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Register admin_post hooks
        add_action('admin_post_save_xenice_level', [$this, 'handle_save']);
        add_action('admin_post_delete_xenice_level', [$this, 'handle_delete']);
    }

    /**
     * Render editor form
     */
    public function render_editor() {
        $level = [
            'id' => 0,
            'name' => '',
            'duration' => '',
            'permissions' => []
        ];
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Loading item for editing (read-only operation).
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            $levels = get_option('xenice_member_levels', []);
            foreach ($levels as $l) {
                if ($l['id'] === $id) {
                    $level = $l;
                    break;
                }
            }
        }
        ?>
        <div class="wrap">
            <h1>
                <?php 
                    echo $level['id'] 
                        ? esc_html__('Edit Membership Level', 'xenice-member') 
                        : esc_html__('Add Membership Level', 'xenice-member'); 
                ?>
            </h1>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('save_level'); ?>
                <input type="hidden" name="action" value="save_xenice_level">
                <input type="hidden" name="id" value="<?php echo esc_attr($level['id']); ?>">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="name"><?php echo esc_html__('Level Name', 'xenice-member'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="name" id="name"
                                   value="<?php echo esc_attr($level['name']); ?>"
                                   class="regular-text" required>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="duration"><?php echo esc_html__('Duration', 'xenice-member'); ?></label>
                        </th>
                        <td>
                            <select name="duration" id="duration">
                                <?php
                                $options = apply_filters('xenice_member_duration_options', []);
                                $current = isset($level['duration']) ? $level['duration'] : '0';

                                foreach ($options as $val => $label) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr($val),
                                        selected($current, $val, false),
                                        esc_html($label)
                                    );
                                }
                                ?>
                            </select>
                            <p class="description"><?php echo esc_html__('Please select the validity period.', 'xenice-member'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php echo esc_html__('Permissions', 'xenice-member'); ?></th>
                        <td>
                            <fieldset>
                                <?php 
                                $permission_list = apply_filters('xenice_member_permission_options', []);
                                foreach ($permission_list as $key => $label): ?>
                                    <label>
                                        <input type="checkbox"
                                               name="permissions[]"
                                               value="<?php echo esc_attr($key); ?>"
                                               <?php checked(in_array($key, $level['permissions'] ?? [])); ?>>
                                        <?php echo esc_html($label); ?>
                                    </label><br>
                                <?php endforeach; ?>
                            </fieldset>
                        </td>
                    </tr>

                </table>

                <?php 
                submit_button(
                    $level['id'] ? esc_html__('Update Level', 'xenice-member') : esc_html__('Add Level', 'xenice-member')
                ); 
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handle save
     */
    public function handle_save() {
        // Check nonce
        if (!isset($_POST['_wpnonce'])) {
            wp_die(esc_html__('Security check failed.', 'xenice-member'));
        }
        $nonce = sanitize_text_field(wp_unslash($_POST['_wpnonce']));
        if (!wp_verify_nonce($nonce, 'save_level')) {
            wp_die(esc_html__('Security check failed.', 'xenice-member'));
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $duration = isset($_POST['duration']) ? sanitize_text_field(wp_unslash($_POST['duration'])) : '';
        $permissions = isset($_POST['permissions']) ? array_map('sanitize_text_field', wp_unslash($_POST['permissions'])) : [];

        $levels = get_option('xenice_member_levels', []);

        if ($id > 0) {
            foreach ($levels as &$level) {
                if ($level['id'] === $id) {
                    $level['name'] = $name;
                    $level['duration'] = $duration;
                    $level['permissions'] = $permissions;
                    break;
                }
            }
        } else {
            $new_id = 1;
            foreach ($levels as $level_item) {
                if ($level_item['id'] >= $new_id) {
                    $new_id = $level_item['id'] + 1;
                }
            }
            $levels[] = [
                'id' => $new_id,
                'name' => $name,
                'duration' => $duration,
                'permissions' => $permissions
            ];
        }

        update_option('xenice_member_levels', $levels);
        wp_safe_redirect(admin_url('admin.php?page=xenice-member-levels'));
        exit;
    }

    /**
     * Handle delete
     */
    public function handle_delete() {
        // Check nonce
        if (!isset($_GET['_wpnonce'])) {
            wp_die(esc_html__('Security check failed.', 'xenice-member'));
        }
        $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));
        if (!wp_verify_nonce($nonce, 'delete_level')) {
            wp_die(esc_html__('Security check failed.', 'xenice-member'));
        }

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $levels = get_option('xenice_member_levels', []);

        foreach ($levels as $key => $level) {
            if ($level['id'] === $id) {
                unset($levels[$key]);
                break;
            }
        }

        update_option('xenice_member_levels', array_values($levels));
        wp_safe_redirect(admin_url('admin.php?page=xenice-member-levels'));
        exit;
    }
}
