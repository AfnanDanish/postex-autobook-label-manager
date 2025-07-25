<?php
/*
Plugin Name: PostEx AutoBook & Label Manager
Description: Automatically book WooCommerce COD orders with PostEx, manage tracking, airway bills, and more.
Version: 1.0.0
Author: Your Name
License: GPL2
Text Domain: postex-autobook-label-manager
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin path
if ( ! defined( 'POSTEX_PLUGIN_PATH' ) ) {
    define( 'POSTEX_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

// Include core files (to be created)
require_once POSTEX_PLUGIN_PATH . 'includes/settings.php';
require_once POSTEX_PLUGIN_PATH . 'includes/hooks.php';
require_once POSTEX_PLUGIN_PATH . 'includes/metabox.php';
require_once POSTEX_PLUGIN_PATH . 'includes/bulk-actions.php';
require_once POSTEX_PLUGIN_PATH . 'includes/pickup-address.php';
require_once POSTEX_PLUGIN_PATH . 'includes/tracking-widget.php'; 