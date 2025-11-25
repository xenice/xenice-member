<?php
if (!defined('ABSPATH')) {
    exit;
}

class Xenice_WC_Free_Download {
    
    public function __construct() {
        add_filter('xenice_member_permission_options', [$this, 'permission_options']);
        add_action('woocommerce_after_add_to_cart_button', [$this, 'display_free_download']);
    }
                    
    public function permission_options($permission_list){
        $permission_list = array(
            'wc_free_download' => esc_html__('WooCommerce Free Download', 'xenice-member'),
        );
        return $permission_list;
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
            // Single file - direct download
            $file = reset($files);
            ?>
            <a href="<?php echo esc_url($file['file']); ?>" 
               class="single_add_to_cart_button button alt" 
               style="margin-left:10px;" 
               download>
               <?php echo esc_html__('Free Download', 'xenice-member'); ?>
            </a>
        <?php else: ?>
            <!-- Multiple files - show popup -->
            <a href="javascript:void(0);" 
               class="single_add_to_cart_button button alt wp-element-button" 
               style="margin-left:10px;" 
               onclick="document.getElementById('<?php echo esc_js($popup_id); ?>').classList.add('active');">
               <?php echo esc_html__('Free Download', 'xenice-member'); ?>
            </a>
    
            <!-- Popup HTML -->
            <div id="<?php echo esc_attr($popup_id); ?>" class="download-popup">
                <div class="download-popup-inner">
                    <h2><?php echo esc_html__('Please select a file to download', 'xenice-member'); ?></h2>

                    <button type="button" 
                            class="download-popup-close" 
                            onclick="document.getElementById('<?php echo esc_js($popup_id); ?>').classList.remove('active')">×</button>
    
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
    
            <style>
                .download-popup {
                    display: none;
                    position: fixed;
                    top:0; left:0;
                    width:100%;
                    height:100%;
                    background: rgba(0,0,0,0.6);
                    z-index: 9999;
                    overflow-y: auto;
                    padding: 20px;
                    transition: opacity 0.3s ease;
                }
                .download-popup.active {
                    display: block;
                }
    
                .download-popup-inner {
                    background: #fff;
                    max-width: 400px;
                    margin: 50px auto;
                    padding: 25px;
                    border-radius: 10px;
                    text-align: center;
                    position: relative;
                    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
                }
    
                .download-popup-close {
                    position: absolute;
                    top: 10px;
                    right: 15px;
                    background: transparent;
                    border: none;
                    font-size: 24px;
                    cursor: pointer;
                }
    
                .download-file-list {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    margin-top: 20px;
                }
    
                .download-file-btn {
                    display: inline-block;
                    width: 100%;
                    padding: 10px 15px;
                    text-align: center;
                    border-radius: 5px;
                }
    
                @media (max-width: 480px) {
                    .download-popup-inner {
                        width: 90%;
                        margin: 30px auto;
                        padding: 20px;
                    }
                }
            </style>
    
            <script>
                (function(){
                    const popup = document.getElementById('<?php echo esc_js($popup_id); ?>');
                    const inner = popup.querySelector('.download-popup-inner');
    
                    // Close when clicking outside the popup
                    popup.addEventListener('click', function(e){
                        if(!inner.contains(e.target)){
                            popup.classList.remove('active');
                        }
                    });
                })();
            </script>
    
        <?php endif; ?>
    
    <?php }
}
