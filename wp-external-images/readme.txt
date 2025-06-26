=== WP External Images ===
Contributors: yourname
Tags: images, external, media, woocommerce, performance
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Use external image URLs directly without storing them in WordPress database, reducing server load and storage requirements.

== Description ==

WP External Images allows you to use external image URLs directly in WordPress without downloading and storing them locally. This is perfect for:

* Using images stored on AWS S3, Google Drive, or other cloud services
* Reducing database size and server storage requirements
* Improving performance by serving images from CDNs
* Managing large image libraries without local storage concerns
* WooCommerce product images from external sources

= Features =

* Use external image URLs directly in media library
* WooCommerce integration for product images
* URL validation and security controls
* Domain whitelist for allowed sources
* Easy-to-use interface integrated with WordPress media uploader
* No impact on existing local images
* Lightweight and performance-focused

= WooCommerce Integration =

* Add external images to product galleries
* Use external URLs for featured product images
* Manage external product images from product edit screen
* Full compatibility with WooCommerce themes and plugins

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wp-external-images/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > External Images to configure options
4. Start adding external images through Media Library or WooCommerce products

== Usage ==

= Adding External Images =

1. Go to Media Library
2. Click "Add External Image" button
3. Enter the external image URL
4. The image will be added to your media library without downloading

= WooCommerce Products =

1. Edit a product
2. Find the "External Product Images" meta box
3. Add external image URLs directly
4. Images will display on product pages

= Settings =

* Enable/disable external image functionality
* Enable URL validation to check if images are accessible
* Set allowed domains to restrict image sources
* Configure security and validation options

== Frequently Asked Questions ==

= Will this work with my existing images? =

Yes, the plugin only affects new external images. Your existing local images remain unchanged.

= What happens if an external image is unavailable? =

If URL validation is enabled, the plugin will check image accessibility before adding. You can also set up fallback images in your theme.

= Does this work with image optimization plugins? =

External images bypass local optimization plugins. Consider using a CDN with built-in optimization for external images.

= Is this compatible with WooCommerce? =

Yes, the plugin includes full WooCommerce integration for product images and galleries.

== Screenshots ==

1. External Images settings page
2. Adding external image in media library
3. WooCommerce product external images meta box
4. External image in media library view

== Changelog ==

= 1.0.0 =
* Initial release
* Basic external image functionality
* WooCommerce integration
* Settings panel
* URL validation
* Domain restrictions

== Upgrade Notice ==

= 1.0.0 =
Initial release of WP External Images plugin.