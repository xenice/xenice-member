<?php
if (!defined('ABSPATH')) {
    exit;
}

class Xenice_WC_Free_Download {
    
    public function __construct() {
        add_filter('xenice_member_permission_options', [$this, 'permission_options']);
        add_action('woocommerce_after_add_to_cart_button', [$this, 'display_free_download']);
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets']);
    }
                    
    public function permission_options($permission_list){
        $permission_list['wc_free_download'] = esc_html__('WooCommerce Free Download', 'xenice-member');
        return $permission_list;
    }


    public function maybe_enqueue_assets() {
        if (!is_product()) {
            return;
        }

        $product_id = get_the_ID();
        if (!$product_id) {
            return;
        }
    
        $product = wc_get_product($product_id);
        
        if (!$product || !$product->is_downloadable()) {
            return;
        }

        if (!xenice_member_can('wc_free_download')) {
            return;
        }

        $files = $product->get_files();
        if (empty($files) || count($files) <= 1) {
            return; 
        }


        wp_enqueue_style(
            'xenice-wc-free-download',
            XENICE_MEMBER_PLUGIN_URL . 'assets/css/wc-free-download.css',
            array(),
            '1.0.0'
        );


        wp_enqueue_script(
            'xenice-wc-free-download',
            XENICE_MEMBER_PLUGIN_URL . 'assets/js/wc-free-download.js',
            array(),
            '1.0.0',
            true
        );
    }
    
    public function display_free_download() {
        global $product;
    
        if (!$product->is_downloadable()) {
            return;
        }
        
        if (!xenice_member_can('wc_free_download')) {
            return;
        }
        
        $files = $product->get_files();
        if (empty($files)) {
            return;
        }
    
        $count_files = count($files);
        $popup_id = 'download-popup-' . esc_attr($product->get_id());
        ?>
    
        <?php if ($count_files === 1): 
            $file = reset($files);
            ?>
            <a href="<?php echo esc_url($file['file']); ?>" 
               class="single_add_to_cart_button button alt" 
               style="margin-left:10px;" 
               download>
               <?php echo esc_html__('Free Download', 'xenice-member'); ?>
            </a>
        <?php else: ?>
            <a href="javascript:void(0);" 
               class="single_add_to_cart_button button alt wp-element-button" 
               style="margin-left:10px;" 
               onclick="document.getElementById('<?php echo esc_js($popup_id); ?>').classList.add('active');">
               <?php echo esc_html__('Free Download', 'xenice-member'); ?>
            </a>
    
            <div id="<?php echo esc_attr($popup_id); ?>" class="download-popup">
                <div class="download-popup-inner">
                    <h2><?php echo esc_html__('Please select a file to download', 'xenice-member'); ?></h2>
                    <button type="button" 
                            class="download-popup-close">×</button>
                    <div class="download-file-list">
                        <?php foreach ($files as $file): ?>
                            <a href="<?php echo esc_url($file['file']); ?>" 
                               class="button alt download-file-btn" 
                               download>
                                <?php echo esc_html($file['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php }
}