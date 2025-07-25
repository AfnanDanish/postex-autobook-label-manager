<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add settings section to WooCommerce > Settings > PostEx
defined('POSTEX_SETTINGS_SECTION') || define('POSTEX_SETTINGS_SECTION', 'postex_settings');

defined('POSTEX_API_TOKEN_OPTION') || define('POSTEX_API_TOKEN_OPTION', 'postex_api_token');

add_filter('woocommerce_get_sections_products', function($sections) {
    $sections['postex'] = __('PostEx', 'postex-autobook-label-manager');
    return $sections;
});

add_filter('woocommerce_get_settings_products', function($settings, $current_section) {
    if ($current_section === 'postex') {
        // Fetch pickup addresses for dropdown
        $addresses = function_exists('postex_get_merchant_pickup_address') ? postex_get_merchant_pickup_address() : [];
        $address_options = ['' => __('Select address', 'postex-autobook-label-manager')];
        if ($addresses && is_array($addresses)) {
            foreach ($addresses as $addr) {
                $label = (isset($addr['addressCode']) ? $addr['addressCode'] : '') . ' - ' . (isset($addr['address']) ? $addr['address'] : '');
                $address_options[$addr['addressCode']] = $label;
            }
        }
        $settings = [
            [
                'title'    => __('PostEx API Settings', 'postex-autobook-label-manager'),
                'type'     => 'title',
                'desc'     => __('Configure your PostEx API token for order booking and tracking.', 'postex-autobook-label-manager'),
                'id'       => 'postex_settings_title',
            ],
            [
                'title'    => __('API Token', 'postex-autobook-label-manager'),
                'desc'     => __('Enter your PostEx API token here.', 'postex-autobook-label-manager'),
                'id'       => POSTEX_API_TOKEN_OPTION,
                'type'     => 'text',
                'desc_tip' => true,
            ],
            [
                'title'    => __('Default Pickup Address', 'postex-autobook-label-manager'),
                'desc'     => __('Select the default pickup address for order booking.', 'postex-autobook-label-manager'),
                'id'       => 'postex_pickup_address_code',
                'type'     => 'select',
                'options'  => $address_options,
                'default'  => '001',
            ],
            [
                'title'    => __('Default Order Note', 'postex-autobook-label-manager'),
                'desc'     => __('This note will be pre-filled for each order in bulk booking.', 'postex-autobook-label-manager'),
                'id'       => 'postex_default_order_note',
                'type'     => 'text',
                'default'  => 'CONTACT CUSTOMER ON PHONE NUMBER',
            ],
            [
                'type' => 'sectionend',
                'id'   => 'postex_settings_section_end',
            ],
        ];
    }
    return $settings;
}, 10, 2); 