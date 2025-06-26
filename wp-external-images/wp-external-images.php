<?php
/**
 * Plugin Name: WP External Images
 * Plugin URI: https://yourwebsite.com
 * Description: Allows using external image URLs directly without storing them in WordPress database
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-external-images
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WPExternalImages {
    
    private $options;
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        
        // Hook into WordPress media handling
        add_filter('wp_handle_sideload', array($this, 'handle_external_images'), 10, 2);
        add_filter('wp_handle_upload', array($this, 'handle_external_images'), 10, 2);
        
        // Handle AJAX requests for external images
        add_action('wp_ajax_add_external_image', array($this, 'ajax_add_external_image'));
        add_action('wp_ajax_nopriv_add_external_image', array($this, 'ajax_add_external_image'));
        
        // Modify media uploader
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Handle external URLs in media library
        add_filter('wp_get_attachment_url', array($this, 'get_external_attachment_url'), 10, 2);
        add_filter('wp_get_attachment_image_src', array($this, 'get_external_attachment_image_src'), 10, 4);
        
        // WooCommerce compatibility
        add_filter('woocommerce_product_get_image', array($this, 'woocommerce_external_image'), 10, 5);
        
        // Prevent WordPress from trying to generate thumbnails for external images
        add_filter('wp_generate_attachment_metadata', array($this, 'skip_external_image_metadata'), 10, 2);
    }
    
    public function init() {
        $this->options = get_option('wp_external_images_options', array(
            'enabled' => true,
            'allow_external_urls' => true,
            'validate_urls' => true,
            'allowed_domains' => ''
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook === 'upload.php' || $hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script(
                'wp-external-images-admin',
                plugin_dir_url(__FILE__) . 'assets/admin.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            wp_localize_script('wp-external-images-admin', 'wpExternalImages', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_external_images_nonce')
            ));
            
            wp_enqueue_style(
                'wp-external-images-admin',
                plugin_dir_url(__FILE__) . 'assets/admin.css',
                array(),
                '1.0.0'
            );
        }
    }
    
    public function ajax_add_external_image() {
        check_ajax_referer('wp_external_images_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_die(__('You do not have permission to upload files.'));
        }
        
        $url = sanitize_url($_POST['url']);
        
        if (!$this->is_valid_image_url($url)) {
            wp_send_json_error(__('Invalid image URL provided.'));
        }
        
        // Create attachment without downloading the file
        $attachment_id = $this->create_external_attachment($url);
        
        if ($attachment_id) {
            $response = array(
                'id' => $attachment_id,
                'url' => $url,
                'title' => basename($url),
                'filename' => basename($url)
            );
            wp_send_json_success($response);
        } else {
            wp_send_json_error(__('Failed to create external image attachment.'));
        }
    }
    
    private function create_external_attachment($url) {
        $filename = basename($url);
        $title = sanitize_title(pathinfo($filename, PATHINFO_FILENAME));
        
        // Create attachment post
        $attachment = array(
            'post_mime_type' => $this->get_mime_type_from_url($url),
            'post_title' => $title,
            'post_content' => '',
            'post_status' => 'inherit',
            'post_author' => get_current_user_id()
        );
        
        $attachment_id = wp_insert_attachment($attachment);
        
        if (!is_wp_error($attachment_id)) {
            // Store the external URL as metadata
            update_post_meta($attachment_id, '_wp_external_image_url', $url);
            update_post_meta($attachment_id, '_wp_external_image', true);
            update_post_meta($attachment_id, '_wp_attached_file', $url);
            
            // Create basic metadata without generating thumbnails
            $metadata = array(
                'width' => 0,
                'height' => 0,
                'file' => $url,
                'external' => true
            );
            
            wp_update_attachment_metadata($attachment_id, $metadata);
            
            return $attachment_id;
        }
        
        return false;
    }
    
    private function is_valid_image_url($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        if ($this->options['validate_urls']) {
            // Check if URL is accessible
            $response = wp_remote_head($url);
            if (is_wp_error($response)) {
                return false;
            }
            
            $content_type = wp_remote_retrieve_header($response, 'content-type');
            if (!strpos($content_type, 'image/') === 0) {
                return false;
            }
        }
        
        // Check allowed domains if specified
        if (!empty($this->options['allowed_domains'])) {
            $allowed_domains = array_map('trim', explode("\n", $this->options['allowed_domains']));
            $url_host = parse_url($url, PHP_URL_HOST);
            $is_allowed = false;
            
            foreach ($allowed_domains as $domain) {
                if (strpos($url_host, $domain) !== false) {
                    $is_allowed = true;
                    break;
                }
            }
            
            if (!$is_allowed) {
                return false;
            }
        }
        
        return true;
    }
    
    private function get_mime_type_from_url($url) {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        $mime_types = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml'
        );
        
        return isset($mime_types[$extension]) ? $mime_types[$extension] : 'image/jpeg';
    }
    
    public function get_external_attachment_url($url, $attachment_id) {
        if (get_post_meta($attachment_id, '_wp_external_image', true)) {
            $external_url = get_post_meta($attachment_id, '_wp_external_image_url', true);
            if ($external_url) {
                return $external_url;
            }
        }
        return $url;
    }
    
    public function get_external_attachment_image_src($image, $attachment_id, $size, $icon) {
        if (get_post_meta($attachment_id, '_wp_external_image', true)) {
            $external_url = get_post_meta($attachment_id, '_wp_external_image_url', true);
            if ($external_url) {
                return array($external_url, 0, 0, false);
            }
        }
        return $image;
    }
    
    public function skip_external_image_metadata($metadata, $attachment_id) {
        if (get_post_meta($attachment_id, '_wp_external_image', true)) {
            return array(
                'width' => 0,
                'height' => 0,
                'file' => get_post_meta($attachment_id, '_wp_external_image_url', true),
                'external' => true
            );
        }
        return $metadata;
    }
    
    public function woocommerce_external_image($image, $product, $size, $attr, $placeholder) {
        // Handle WooCommerce product images
        $attachment_id = get_post_thumbnail_id($product->get_id());
        if ($attachment_id && get_post_meta($attachment_id, '_wp_external_image', true)) {
            $external_url = get_post_meta($attachment_id, '_wp_external_image_url', true);
            if ($external_url) {
                $alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                $default_attr = array(
                    'src' => $external_url,
                    'alt' => $alt ? $alt : $product->get_name(),
                    'class' => 'wp-external-image'
                );
                $attr = wp_parse_args($attr, $default_attr);
                return '<img ' . implode(' ', array_map(function($k, $v) {
                    return $k . '="' . esc_attr($v) . '"';
                }, array_keys($attr), $attr)) . '>';
            }
        }
        return $image;
    }
    
    public function handle_external_images($file, $overrides = array()) {
        // This can be extended to handle specific upload scenarios
        return $file;
    }
    
    // Admin settings
    public function add_admin_menu() {
        add_options_page(
            'WP External Images',
            'External Images',
            'manage_options',
            'wp-external-images',
            array($this, 'options_page')
        );
    }
    
    public function settings_init() {
        register_setting('wp_external_images', 'wp_external_images_options');
        
        add_settings_section(
            'wp_external_images_section',
            __('External Images Settings', 'wp-external-images'),
            array($this, 'settings_section_callback'),
            'wp_external_images'
        );
        
        add_settings_field(
            'enabled',
            __('Enable External Images', 'wp-external-images'),
            array($this, 'enabled_render'),
            'wp_external_images',
            'wp_external_images_section'
        );
        
        add_settings_field(
            'validate_urls',
            __('Validate URLs', 'wp-external-images'),
            array($this, 'validate_urls_render'),
            'wp_external_images',
            'wp_external_images_section'
        );
        
        add_settings_field(
            'allowed_domains',
            __('Allowed Domains', 'wp-external-images'),
            array($this, 'allowed_domains_render'),
            'wp_external_images',
            'wp_external_images_section'
        );
    }
    
    public function enabled_render() {
        ?>
        <input type='checkbox' name='wp_external_images_options[enabled]' <?php checked($this->options['enabled'], 1); ?> value='1'>
        <p class="description">Enable external image functionality</p>
        <?php
    }
    
    public function validate_urls_render() {
        ?>
        <input type='checkbox' name='wp_external_images_options[validate_urls]' <?php checked($this->options['validate_urls'], 1); ?> value='1'>
        <p class="description">Validate that URLs are accessible and contain images</p>
        <?php
    }
    
    public function allowed_domains_render() {
        ?>
        <textarea name='wp_external_images_options[allowed_domains]' rows="5" cols="50"><?php echo esc_textarea($this->options['allowed_domains']); ?></textarea>
        <p class="description">One domain per line. Leave empty to allow all domains. Example: s3.amazonaws.com</p>
        <?php
    }
    
    public function settings_section_callback() {
        echo __('Configure settings for external images functionality.', 'wp-external-images');
    }
    
    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>WP External Images</h2>
            <?php
            settings_fields('wp_external_images');
            do_settings_sections('wp_external_images');
            submit_button();
            ?>
        </form>
        <?php
    }
}

new WPExternalImages();