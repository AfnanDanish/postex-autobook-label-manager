<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add bulk actions to WooCommerce Orders
add_filter('bulk_actions-edit-shop_order', function($bulk_actions) {
    $bulk_actions['postex_bulk_book'] = __('Book with PostEx', 'postex-autobook-label-manager');
    $bulk_actions['postex_bulk_loadsheet'] = __('Generate PostEx Load Sheet', 'postex-autobook-label-manager');
    return $bulk_actions;
});

add_action('admin_menu', function() {
    add_submenu_page(
        null, // hidden from menu
        __('Bulk PostEx Booking Review', 'postex-autobook-label-manager'),
        __('Bulk PostEx Booking Review', 'postex-autobook-label-manager'),
        'manage_woocommerce',
        'postex-bulk-book-review',
        'postex_bulk_book_review_page'
    );
});

function postex_get_operational_cities() {
    $api_token = get_option('postex_api_token');
    if ( ! $api_token ) return [];
    $url = 'https://api.postex.pk/services/integration/api/order/v2/get-operational-city';
    $response = wp_remote_get($url, [
        'headers' => [
            'token' => $api_token,
        ],
        'timeout' => 20,
    ]);
    if ( is_wp_error($response) ) return [];
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ( isset($body['dist']) && is_array($body['dist']) ) {
        $cities = [];
        foreach ($body['dist'] as $city) {
            if (!empty($city['operationalCityName'])) {
                $cities[] = $city['operationalCityName'];
            }
        }
        return $cities;
    }
    return [];
}

function postex_find_best_city_match($user_city, $operational_cities) {
    if (empty($user_city) || empty($operational_cities)) {
        return '';
    }
    
    $user_city_lower = strtolower(trim($user_city));
    
    // First: Try exact match (case insensitive)
    foreach ($operational_cities as $city) {
        if (strtolower(trim($city)) === $user_city_lower) {
            return $city;
        }
    }
    
    // Second: Try partial match (user city contains operational city or vice versa)
    foreach ($operational_cities as $city) {
        $city_lower = strtolower(trim($city));
        if (strpos($user_city_lower, $city_lower) !== false || strpos($city_lower, $user_city_lower) !== false) {
            return $city;
        }
    }
    
    // Third: Try common city name variations
    $city_variations = [
        'lahore' => ['lahore', 'lhr'],
        'karachi' => ['karachi', 'khi'],
        'islamabad' => ['islamabad', 'isb'],
        'rawalpindi' => ['rawalpindi', 'rwp'],
        'faisalabad' => ['faisalabad', 'fsd'],
        'multan' => ['multan', 'mtn'],
        'peshawar' => ['peshawar', 'pwr'],
        'quetta' => ['quetta', 'qta'],
        'hyderabad' => ['hyderabad', 'hyd'],
        'gujranwala' => ['gujranwala', 'gjw'],
        'sialkot' => ['sialkot', 'skt']
    ];
    
    foreach ($city_variations as $standard => $variations) {
        if (in_array($user_city_lower, $variations)) {
            foreach ($operational_cities as $city) {
                if (strtolower(trim($city)) === $standard) {
                    return $city;
                }
            }
        }
    }
    
    // Return first city if no match found (fallback)
    return !empty($operational_cities) ? $operational_cities[0] : '';
}

function postex_bulk_book_review_page() {
    if (empty($_GET['order_ids'])) {
        echo '<div class="notice notice-error"><p>' . esc_html__('No orders selected.', 'postex-autobook-label-manager') . '</p></div>';
        return;
    }
    $order_ids = array_map('intval', explode(',', sanitize_text_field($_GET['order_ids'])));
    if (!current_user_can('manage_woocommerce')) return;
    $cities = postex_get_operational_cities();
    $pickup_addresses = function_exists('postex_get_merchant_pickup_address') ? postex_get_merchant_pickup_address() : [];
    $default_note = get_option('postex_default_order_note', 'CONTACT CUSTOMER ON PHONE NUMBER');
    echo '<div class="wrap"><h1>' . esc_html__('Review & Edit Orders Before PostEx Booking', 'postex-autobook-label-manager') . '</h1>';
    echo '<form method="post">';
    wp_nonce_field('postex_bulk_book_review');
    echo '<table class="widefat"><thead><tr>';
    echo '<th>' . esc_html__('Order ID', 'postex-autobook-label-manager') . '</th>';
    echo '<th>' . esc_html__('Customer Name', 'postex-autobook-label-manager') . '</th>';
    echo '<th>' . esc_html__('Phone', 'postex-autobook-label-manager') . '</th>';
    echo '<th>' . esc_html__('Address', 'postex-autobook-label-manager') . '</th>';
    echo '<th>' . esc_html__('City', 'postex-autobook-label-manager') . '</th>';
    echo '<th>' . esc_html__('Items', 'postex-autobook-label-manager') . '</th>';
    echo '<th>' . esc_html__('COD Amount', 'postex-autobook-label-manager') . '</th>';
    echo '<th>' . esc_html__('Order Ref #', 'postex-autobook-label-manager') . '</th>';
    echo '<th>' . esc_html__('Order Notes', 'postex-autobook-label-manager') . '</th>';
    echo '<th>' . esc_html__('Pickup Address', 'postex-autobook-label-manager') . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) continue;
        $name = esc_attr($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        $phone = esc_attr($order->get_billing_phone());
        $address = esc_attr($order->get_billing_address_1() . ' ' . $order->get_billing_address_2());
        $city = esc_attr($order->get_billing_city());
        $items = [];
        foreach ($order->get_items() as $item) {
            $items[] = $item->get_name() . ' x' . $item->get_quantity();
        }
        $items_str = esc_attr(implode(', ', $items));
        $pickup_code = get_option('postex_pickup_address_code', '001');
        $cod_amount = esc_attr($order->get_total());
        $order_ref = esc_attr($order->get_order_number());
        echo '<tr>';
        echo '<td>' . esc_html($order_id) . '<input type="hidden" name="orders[' . $order_id . '][order_id]" value="' . esc_attr($order_id) . '" /></td>';
        echo '<td><input type="text" name="orders[' . $order_id . '][customerName]" value="' . $name . '" /></td>';
        echo '<td><input type="text" name="orders[' . $order_id . '][customerPhone]" value="' . $phone . '" /></td>';
        echo '<td><input type="text" name="orders[' . $order_id . '][deliveryAddress]" value="' . $address . '" /></td>';
        // City dropdown
        $best_match = postex_find_best_city_match($city, $cities);
        echo '<td><select name="orders[' . $order_id . '][cityName]">';
        foreach ($cities as $city_option) {
            $selected = ($city_option === $best_match) ? 'selected' : '';
            echo '<option value="' . esc_attr($city_option) . '" ' . $selected . '>' . esc_html($city_option) . '</option>';
        }
        echo '</select></td>';
        echo '<td><input type="text" name="orders[' . $order_id . '][orderDetail]" value="' . $items_str . '" /></td>';
        echo '<td><input type="number" step="0.01" name="orders[' . $order_id . '][invoicePayment]" value="' . $cod_amount . '" /></td>';
        echo '<td><input type="text" name="orders[' . $order_id . '][orderRefNumber]" value="' . $order_ref . '" /></td>';
        echo '<td><input type="text" name="orders[' . $order_id . '][transactionNotes]" value="' . esc_attr($default_note) . '" /></td>';
        // Pickup address dropdown
        echo '<td><select name="orders[' . $order_id . '][pickupAddressCode]">';
        foreach ($pickup_addresses as $addr) {
            $selected = ($pickup_code == $addr['addressCode']) ? 'selected' : '';
            $label = esc_html($addr['addressCode'] . ' - ' . $addr['address']);
            echo '<option value="' . esc_attr($addr['addressCode']) . '" ' . $selected . '>' . $label . '</option>';
        }
        echo '</select></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<p><input type="submit" class="button button-primary" name="postex_bulk_book_confirm" value="' . esc_attr__('Book All with PostEx', 'postex-autobook-label-manager') . '" /></p>';
    echo '</form></div>';
}

add_filter('handle_bulk_actions-edit-shop_order', function($redirect_to, $action, $order_ids) {
    if ($action === 'postex_bulk_book') {
        $url = add_query_arg([
            'page' => 'postex-bulk-book-review',
            'order_ids' => implode(',', $order_ids),
        ], admin_url('admin.php'));
        wp_redirect($url); exit;
    }
    $api_token = get_option('postex_api_token');
    $pickup_address_code = get_option('postex_pickup_address_code', '001');
    if ( ! $api_token ) return $redirect_to;
    if ( $action === 'postex_bulk_book' ) {
        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order($order_id);
            if ( ! $order ) continue;
            if ( $order->get_payment_method() !== 'cod' ) continue;
            if ( get_post_meta($order_id, '_postex_tracking_number', true) ) continue;
            // Prepare data (same as in hooks.php)
            $billing = [
                'customerName'    => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'customerPhone'   => $order->get_billing_phone(),
                'deliveryAddress' => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
                'cityName'        => $order->get_billing_city(),
            ];
            $order_items = $order->get_items();
            $items_count = $order->get_item_count();
            $order_detail = [];
            foreach ( $order_items as $item ) {
                $order_detail[] = $item->get_name() . ' x' . $item->get_quantity();
            }
            $order_detail_str = implode(', ', $order_detail);
            $postex_data = [
                'cityName'        => $billing['cityName'],
                'customerName'    => $billing['customerName'],
                'customerPhone'   => $billing['customerPhone'],
                'deliveryAddress' => $billing['deliveryAddress'],
                'invoiceDivision' => 1,
                'invoicePayment'  => (float) $order->get_total(),
                'items'           => $items_count,
                'orderDetail'     => $order_detail_str,
                'orderRefNumber'  => $order->get_order_number(),
                'orderType'       => 'Normal',
                'transactionNotes'=> '',
                'pickupAddressCode' => $pickup_address_code,
            ];
            $response = wp_remote_post('https://api.postex.pk/services/integration/api/order/v3/create-order', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'token'        => $api_token,
                ],
                'body'    => wp_json_encode($postex_data),
                'timeout' => 30,
            ]);
            if ( is_wp_error($response) ) {
                $order->add_order_note('PostEx Bulk Booking Error: ' . $response->get_error_message());
                continue;
            }
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if ( isset($body['statusCode']) && $body['statusCode'] == '200' && isset($body['dist']['trackingNumber']) ) {
                update_post_meta($order_id, '_postex_tracking_number', $body['dist']['trackingNumber']);
                $order->add_order_note('PostEx Bulk Booking Success. Tracking Number: ' . $body['dist']['trackingNumber']);
            } else {
                $msg = isset($body['statusMessage']) ? $body['statusMessage'] : 'Unknown error';
                $order->add_order_note('PostEx Bulk Booking Failed: ' . $msg);
            }
        }
        $redirect_to = add_query_arg('postex_bulk_booked', count($order_ids), $redirect_to);
    }
    if ( $action === 'postex_bulk_loadsheet' ) {
        $tracking_numbers = [];
        foreach ( $order_ids as $order_id ) {
            $tracking = get_post_meta($order_id, '_postex_tracking_number', true);
            if ( $tracking ) $tracking_numbers[] = $tracking;
        }
        if ( ! empty($tracking_numbers) ) {
            $postex_data = [
                'pickupAddress' => '', // Optional: can be set from settings or meta
                'trackingNumbers' => $tracking_numbers,
            ];
            $response = wp_remote_post('https://api.postex.pk/services/integration/api/order/v2/generate-load-sheet', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'token'        => $api_token,
                ],
                'body'    => wp_json_encode($postex_data),
                'timeout' => 60,
            ]);
            // Add note to all selected orders
            foreach ( $order_ids as $order_id ) {
                $order = wc_get_order($order_id);
                if ( is_wp_error($response) ) {
                    $order->add_order_note('PostEx Load Sheet Error: ' . $response->get_error_message());
                } else {
                    $body = wp_remote_retrieve_body($response);
                    if ( strpos($body, '%PDF') !== false ) {
                        $order->add_order_note('PostEx Load Sheet generated.');
                    } else {
                        $order->add_order_note('PostEx Load Sheet response: ' . substr($body, 0, 200));
                    }
                }
            }
        }
        $redirect_to = add_query_arg('postex_bulk_loadsheet', count($tracking_numbers), $redirect_to);
    }
    return $redirect_to;
}, 9, 3);

add_action('admin_init', function() {
    if (isset($_POST['postex_bulk_book_confirm']) && isset($_POST['orders']) && check_admin_referer('postex_bulk_book_review')) {
        $api_token = get_option('postex_api_token');
        foreach ($_POST['orders'] as $order_id => $data) {
            $order = wc_get_order($order_id);
            if (!$order) continue;
            $postex_data = [
                'cityName'        => sanitize_text_field($data['cityName']),
                'customerName'    => sanitize_text_field($data['customerName']),
                'customerPhone'   => sanitize_text_field($data['customerPhone']),
                'deliveryAddress' => sanitize_text_field($data['deliveryAddress']),
                'invoiceDivision' => 1,
                'invoicePayment'  => (float) $data['invoicePayment'],
                'items'           => $order->get_item_count(),
                'orderDetail'     => sanitize_text_field($data['orderDetail']),
                'orderRefNumber'  => sanitize_text_field($data['orderRefNumber']),
                'orderType'       => 'Normal',
                'transactionNotes'=> sanitize_text_field($data['transactionNotes']),
                'pickupAddressCode' => sanitize_text_field($data['pickupAddressCode']),
            ];
            $response = wp_remote_post('https://api.postex.pk/services/integration/api/order/v3/create-order', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'token'        => $api_token,
                ],
                'body'    => wp_json_encode($postex_data),
                'timeout' => 30,
            ]);
            if ( is_wp_error($response) ) {
                $order->add_order_note('PostEx Bulk Booking Error: ' . $response->get_error_message());
                continue;
            }
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if ( isset($body['statusCode']) && $body['statusCode'] == '200' && isset($body['dist']['trackingNumber']) ) {
                update_post_meta($order_id, '_postex_tracking_number', $body['dist']['trackingNumber']);
                $order->add_order_note('PostEx Bulk Booking Success. Tracking Number: ' . $body['dist']['trackingNumber']);
            } else {
                $msg = isset($body['statusMessage']) ? $body['statusMessage'] : 'Unknown error';
                $order->add_order_note('PostEx Bulk Booking Failed: ' . $msg);
            }
        }
        wp_redirect(admin_url('edit.php?post_type=shop_order&postex_bulk_booked=1'));
        exit;
    }
}); 