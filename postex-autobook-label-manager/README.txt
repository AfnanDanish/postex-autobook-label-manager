=== PostEx AutoBook & Label Manager ===
Contributors: afnandanish
Tags: postex, courier, shipping, woocommerce, tracking, cod, pakistan
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automate PostEx courier booking, tracking, and management for WooCommerce stores in Pakistan.

== Description ==

PostEx AutoBook & Label Manager is a comprehensive WordPress plugin that integrates your WooCommerce store with PostEx courier services. Perfect for Pakistani e-commerce businesses using Cash on Delivery (COD) payments.

= Key Features =

* **Automatic COD Booking**: Automatically books new WooCommerce COD orders with PostEx
* **Smart City Matching**: Advanced algorithm matches customer cities to PostEx operational cities
* **Bulk Order Management**: Select and book multiple orders at once with review interface
* **Customer Tracking Widget**: Branded tracking widget for customers to track their orders
* **Order Management**: View tracking, cancel bookings, and download airway bills from order admin
* **Shortcode Support**: Use `[postex_tracking_form]` anywhere on your site
* **Complete API Integration**: Full PostEx API v4.1.9 implementation

= Smart City Matching =

Our advanced city matching algorithm ensures accurate delivery locations:
* Exact case-insensitive matching
* Substring and partial matching
* Levenshtein distance similarity scoring
* Phonetic matching for variations
* Common abbreviations (LHR → Lahore, KHI → Karachi)

= Customer Experience =

* Customers can track orders using WooCommerce order numbers or PostEx tracking numbers
* Clean, responsive tracking widget with black and white branding
* Widget available as WordPress widget and shortcode
* Support for tracking with or without # symbol

= For Developers =

* Clean, well-documented code
* WordPress coding standards
* Secure API handling with proper sanitization
* Hooks and filters for customization
* Comprehensive error handling and logging

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/postex-autobook-label-manager/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to WooCommerce > Settings > Products > PostEx
4. Enter your PostEx API token (get from PostEx merchant dashboard)
5. Select your default pickup address
6. Save settings and test with a COD order

== Frequently Asked Questions ==

= How do I get a PostEx API token? =

Contact PostEx to set up a merchant account and obtain your API credentials. You'll need to be a registered PostEx merchant to use this plugin.

= Which payment methods trigger automatic booking? =

Only orders with "Cash on Delivery" (COD) payment method are automatically booked. This can be customized using plugin hooks.

= Can customers track orders without knowing the PostEx tracking number? =

Yes! Customers can enter their WooCommerce order number (e.g., #1234) and the plugin will look up the associated PostEx tracking number.

= What happens if a city doesn't match? =

The plugin uses smart city matching with similarity scoring. If no good match is found, the order won't be auto-booked and you can manually select the correct city in the bulk booking interface.

= Can I customize the tracking widget appearance? =

Yes, the widget uses CSS classes that can be styled. The default theme is black and white for professional branding.

= Is this plugin secure? =

Yes, the plugin follows WordPress security best practices including nonce verification, capability checks, data sanitization, and secure API token storage.

== Screenshots ==

1. Plugin settings page with API configuration
2. Bulk booking interface with city matching
3. Order metabox showing tracking information
4. Customer tracking widget
5. Smart city matching in action

== Changelog ==

= 1.0.0 =
* Initial release
* Automatic COD order booking with PostEx API
* Smart city matching algorithm with debug mode
* Bulk booking interface with order review
* Customer tracking widget with shortcode support
* Complete order management from WooCommerce admin
* Black and white branded design
* Comprehensive error handling and logging
* Full PostEx API v4.1.9 integration

== Upgrade Notice ==

= 1.0.0 =
First stable release of PostEx AutoBook & Label Manager. Provides complete PostEx integration for WooCommerce stores.

== API Requirements ==

This plugin requires:
* Valid PostEx merchant account
* PostEx API token
* Active internet connection for API calls
* WooCommerce with COD payment method enabled

== Support ==

For support, please:
1. Check the troubleshooting section in plugin documentation
2. Review WooCommerce order notes for API error messages
3. Enable debug mode for detailed city matching information
4. Contact your developer with specific error details

== License ==

This plugin is licensed under GPL v2 or later.

== PostEx Integration ==

Official integration with PostEx courier services in Pakistan. Implements PostEx API v4.1.9 for complete order lifecycle management from booking to delivery tracking. 