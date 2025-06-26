<?php
/**
 * WooCommerce Integration for WP External Images
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPExternalImages_WooCommerce {
    
    public function __construct() {
        // Only load if WooCommerce is active
        if (class_exists('WooCommerce')) {
            add_action('init', array($this, 'init'));
        }
    }
    
    public function init() {
        // Product image handling
        add_filter('woocommerce_single_product_image_thumbnail_html', array($this, 'product_image_html'), 10, 2);
        add_filter('woocommerce_product_get_gallery_image_ids', array($this, 'handle_gallery_images'), 10, 2);
        
        // Admin product page integration
        add_action('add_meta_boxes', array($this, 'add_product_meta_boxes'));
        add_action('save_post', array($this, 'save_product_external_images'));
        
        // AJAX handlers for product images
        add_action('wp_ajax_add_external_product_image', array($this, 'ajax_add_external_product_image'));
        add_action('wp_ajax_remove_external_product_image', array($this, 'ajax_remove_external_product_image'));
        add_action('wp_ajax_preview_external_image', array($this, 'ajax_preview_external_image'));
        add_action('wp_ajax_set_external_featured_image', array($this, 'ajax_set_external_featured_image'));
        
        // Enqueue scripts for product admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_product_scripts'));
    }
    
    public function product_image_html($html, $attachment_id) {
        if (get_post_meta($attachment_id, '_wp_external_image', true)) {
            $external_url = get_post_meta($attachment_id, '_wp_external_image_url', true);
            if ($external_url) {
                $alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                return sprintf(
                    '<div data-thumb="%s" data-thumb-alt="%s" class="woocommerce-product-gallery__image">
                        <a href="%s">
                            <img width="600" height="600" src="%s" class="wp-post-image wp-external-image" alt="%s" loading="lazy">
                        </a>
                    </div>',
                    esc_url($external_url),
                    esc_attr($alt),
                    esc_url($external_url),
                    esc_url($external_url),
                    esc_attr($alt)
                );
            }
        }
        return $html;
    }
    
    public function handle_gallery_images($gallery_ids, $product) {
        // Handle external images in product gallery
        $external_gallery = get_post_meta($product->get_id(), '_external_gallery_images', true);
        if ($external_gallery && is_array($external_gallery)) {
            foreach ($external_gallery as $external_image) {
                if (isset($external_image['attachment_id'])) {
                    $gallery_ids[] = $external_image['attachment_id'];
                }
            }
        }
        return $gallery_ids;
    }
    
    public function add_product_meta_boxes() {
        add_meta_box(
            'external-product-images',
            __('External Product Images', 'wp-external-images'),
            array($this, 'product_images_meta_box'),
            'product',
            'side',
            'high'
        );
    }
    
    public function product_images_meta_box($post) {
        wp_nonce_field('external_product_images_nonce', 'external_product_images_nonce');
        
        $external_featured = get_post_meta($post->ID, '_external_featured_image', true);
        $external_gallery = get_post_meta($post->ID, '_external_gallery_images', true);
        
        if (!$external_gallery) {
            $external_gallery = array();
        }
        
        ?>
        <div class="external-images-container">
            <!-- External Featured Image Section -->
            <div class="external-image-section external-featured-section">
                <div class="external-section-header">
                    <h3 class="external-section-title">
                        <span>External Featured Image</span>
                        <div class="external-section-controls">
                            <button type="button" class="external-toggle-btn" data-target="external-featured-content">
                                <span class="dashicons dashicons-arrow-up-alt2"></span>
                            </button>
                        </div>
                    </h3>
                </div>
                <div class="external-section-content" id="external-featured-content">
                    <div class="external-featured-preview">
                        <?php if ($external_featured && !empty($external_featured['url'])): ?>
                            <div class="external-image-preview">
                                <img src="<?php echo esc_url($external_featured['url']); ?>" alt="Featured Image Preview">
                                <div class="external-image-overlay">
                                    <button type="button" class="button button-secondary remove-external-featured">
                                        Remove Image
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="external-image-placeholder">
                                <div class="external-placeholder-content">
                                    <span class="dashicons dashicons-format-image"></span>
                                    <p>No external featured image set</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="external-featured-controls">
                        <input type="url" 
                               id="external-featured-url" 
                               placeholder="Enter image URL..." 
                               value="<?php echo $external_featured ? esc_url($external_featured['url']) : ''; ?>"
                               class="external-url-input">
                        <div class="external-control-buttons">
                            <button type="button" class="button button-secondary preview-external-featured">
                                Preview
                            </button>
                            <button type="button" class="button button-primary set-external-featured">
                                Set Featured Image
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- External Images Gallery Section -->
            <div class="external-image-section external-gallery-section">
                <div class="external-section-header">
                    <h3 class="external-section-title">
                        <span>External Images Gallery</span>
                        <div class="external-section-controls">
                            <button type="button" class="external-toggle-btn" data-target="external-gallery-content">
                                <span class="dashicons dashicons-arrow-up-alt2"></span>
                            </button>
                        </div>
                    </h3>
                </div>
                <div class="external-section-content" id="external-gallery-content">
                    <div class="external-gallery-grid" id="external-gallery-container">
                        <?php foreach ($external_gallery as $index => $image): ?>
                            <div class="external-gallery-item" data-index="<?php echo $index; ?>">
                                <div class="external-gallery-preview">
                                    <img src="<?php echo esc_url($image['url']); ?>" alt="Gallery Image">
                                    <div class="external-gallery-overlay">
                                        <button type="button" class="button button-secondary remove-external-gallery-item">
                                            <span class="dashicons dashicons-no-alt"></span>
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="external_gallery_images[<?php echo $index; ?>][url]" value="<?php echo esc_url($image['url']); ?>">
                                <input type="hidden" name="external_gallery_images[<?php echo $index; ?>][alt]" value="<?php echo esc_attr($image['alt']); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="external-gallery-controls">
                        <input type="url" 
                               id="external-gallery-url" 
                               placeholder="Image URL..." 
                               class="external-url-input">
                        <div class="external-control-buttons">
                            <button type="button" class="button button-secondary preview-external-gallery">
                                Preview
                            </button>
                            <button type="button" class="button button-primary add-external-gallery">
                                Add to Gallery
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden inputs for data storage -->
        <input type="hidden" id="external-featured-data" name="external_featured_image" value="<?php echo esc_attr(json_encode($external_featured)); ?>">
        <?php
    }
    
    public function save_product_external_images($post_id) {
        if (!isset($_POST['external_product_images_nonce']) || 
            !wp_verify_nonce($_POST['external_product_images_nonce'], 'external_product_images_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save external featured image
        if (isset($_POST['external_featured_image'])) {
            $featured_data = json_decode(stripslashes($_POST['external_featured_image']), true);
            if ($featured_data && !empty($featured_data['url'])) {
                update_post_meta($post_id, '_external_featured_image', $featured_data);
            } else {
                delete_post_meta($post_id, '_external_featured_image');
            }
        }
        
        // Save external gallery images
        if (isset($_POST['external_gallery_images'])) {
            $gallery_images = array();
            foreach ($_POST['external_gallery_images'] as $image_data) {
                if (!empty($image_data['url'])) {
                    $gallery_images[] = array(
                        'url' => sanitize_url($image_data['url']),
                        'alt' => sanitize_text_field($image_data['alt'])
                    );
                }
            }
            update_post_meta($post_id, '_external_gallery_images', $gallery_images);
        } else {
            delete_post_meta($post_id, '_external_gallery_images');
        }
    }
    
    public function enqueue_product_scripts($hook) {
        global $post_type;
        
        if ($post_type === 'product' && ($hook === 'post.php' || $hook === 'post-new.php')) {
            wp_enqueue_script(
                'wp-external-images-woocommerce',
                plugin_dir_url(__FILE__) . '../assets/woocommerce.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            wp_localize_script('wp-external-images-woocommerce', 'wpExternalImages', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_external_images_nonce')
            ));
        }
    }
    
    public function ajax_preview_external_image() {
        check_ajax_referer('wp_external_images_nonce', 'nonce');
        
        $url = sanitize_url($_POST['url']);
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            wp_send_json_error('Invalid URL provided');
        }
        
        // Check if URL is accessible
        $response = wp_remote_head($url);
        if (is_wp_error($response)) {
            wp_send_json_error('Image URL is not accessible');
        }
        
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        if (strpos($content_type, 'image/') !== 0) {
            wp_send_json_error('URL does not point to an image');
        }
        
        wp_send_json_success(array(
            'url' => $url,
            'valid' => true
        ));
    }
    
    public function ajax_set_external_featured_image() {
        check_ajax_referer('wp_external_images_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $image_url = sanitize_url($_POST['image_url']);
        
        if (!current_user_can('edit_post', $product_id)) {
            wp_send_json_error('Permission denied');
        }
        
        $featured_data = array(
            'url' => $image_url,
            'alt' => sanitize_text_field($_POST['alt_text'])
        );
        
        update_post_meta($product_id, '_external_featured_image', $featured_data);
        
        wp_send_json_success(array(
            'message' => 'External featured image set successfully',
            'image' => $featured_data
        ));
    }
    
    public function ajax_add_external_product_image() {
        check_ajax_referer('wp_external_images_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $image_url = sanitize_url($_POST['image_url']);
        $alt_text = sanitize_text_field($_POST['alt_text']);
        
        if (!current_user_can('edit_post', $product_id)) {
            wp_send_json_error('Permission denied');
        }
        
        $external_images = get_post_meta($product_id, '_external_gallery_images', true);
        if (!$external_images) {
            $external_images = array();
        }
        
        $external_images[] = array(
            'url' => $image_url,
            'alt' => $alt_text
        );
        
        update_post_meta($product_id, '_external_gallery_images', $external_images);
        
        wp_send_json_success(array(
            'message' => 'External image added successfully',
            'image' => array(
                'url' => $image_url,
                'alt' => $alt_text
            )
        ));
    }
}

new WPExternalImages_WooCommerce();