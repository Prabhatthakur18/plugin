<?php
/**
 * Uninstall script for WP External Images
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('wp_external_images_options');

// Clean up any external image metadata
global $wpdb;

// Remove external image metadata
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_wp_external_image'");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_wp_external_image_url'");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_external_product_images'");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_external_gallery_images'");

// Optional: Remove external image attachments (uncomment if desired)
/*
$external_attachments = get_posts(array(
    'post_type' => 'attachment',
    'meta_key' => '_wp_external_image',
    'meta_value' => true,
    'posts_per_page' => -1
));

foreach ($external_attachments as $attachment) {
    wp_delete_attachment($attachment->ID, true);
}
*/