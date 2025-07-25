<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add bulk actions to WooCommerce Orders
add_filter('bulk_actions-edit-shop_order', function($bulk_actions) {
    $bulk_actions['postex_bulk_book'] = __('Book with PostEx', 'postex-autobook-label-manager');
    $bulk_actions['postex_bulk_loadsheet'] = __('Generate PostEx Load Sheet', 'postex-autobook-label-manager');
    $bulk_actions['postex_bulk_airwaybill'] = __('Print Airway Bills (max 10)', 'postex-autobook-label-manager');
    $bulk_actions['postex_bulk_cancel'] = __('Bulk Cancel with PostEx', 'postex-autobook-label-manager');
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
        $city_normalized = strtolower(trim($city));
        // City dropdown with robust matching
        echo '<td><select name="orders[' . $order_id . '][cityName]">';
        $matched = false;
        $best_match_index = null;
        $best_match_score = 0;
        foreach ($cities as $i => $city_option) {
            $city_option_normalized = strtolower(trim($city_option));
            $selected = '';
            // Exact match
            if ($city_option_normalized === $city_normalized && !$matched) {
                $selected = 'selected';
                $matched = true;
            } else if (!$matched) {
                // Partial match (contains or starts with)
                $score = 0;
                if (strpos($city_option_normalized, $city_normalized) !== false || strpos($city_normalized, $city_option_normalized) !== false) {
                    $score += 2;
                }
                if (substr($city_option_normalized, 0, 3) === substr($city_normalized, 0, 3)) {
                    $score += 1;
                }
                // Levenshtein distance (if available)
                if (function_exists('levenshtein')) {
                    $lev = levenshtein($city_option_normalized, $city_normalized);
                    if ($lev < 3) $score += 2;
                    else if ($lev < 5) $score += 1;
                }
                if ($score > $best_match_score) {
                    $best_match_score = $score;
                    $best_match_index = $i;
                }
            }
            echo '<option value="' . esc_attr($city_option) . '" ' . $selected . '>' . esc_html($city_option) . '</option>';
        }
        // If no exact match, select the best fuzzy match
        if (!$matched && $best_match_index !== null) {
            echo "<script>document.addEventListener('DOMContentLoaded',function(){var sel=document.getElementsByName('orders[{$order_id}][cityName]')[0];if(sel)sel.selectedIndex={$best_match_index};});</script>";
        }
        echo '</select></td>';
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
    $api_token = get_option('postex_api_token');
    $pickup_address_code = get_option('postex_pickup_address_code', '001');
    if ( ! $api_token ) return $redirect_to;
    if ( $action === 'postex_bulk_airwaybill' ) {
        $tracking_numbers = [];
        foreach ( $order_ids as $order_id ) {
            $tracking = get_post_meta($order_id, '_postex_tracking_number', true);
            if ( $tracking ) $tracking_numbers[] = $tracking;
            if ( count($tracking_numbers) >= 10 ) break;
        }
        if ( ! empty($tracking_numbers) ) {
            $url = 'https://api.postex.pk/services/integration/api/order/v1/get-invoice?trackingNumbers=' . urlencode(implode(',', $tracking_numbers));
            // Redirect to PDF (let browser handle download/print)
            wp_redirect($url); exit;
        } else {
            $redirect_to = add_query_arg('postex_bulk_airwaybill', 'none', $redirect_to);
        }
    }
    if ( $action === 'postex_bulk_cancel' ) {
        foreach ( $order_ids as $order_id ) {
            $tracking = get_post_meta($order_id, '_postex_tracking_number', true);
            if ( $tracking ) {
                $response = wp_remote_request('https://api.postex.pk/services/integration/api/order/v1/cancel-order', [
                    'method' => 'PUT',
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'token' => $api_token,
                    ],
                    'body' => wp_json_encode(['trackingNumber' => $tracking]),
                    'timeout' => 20,
                ]);
                $order = wc_get_order($order_id);
                if ( is_wp_error($response) ) {
                    $order->add_order_note('PostEx Bulk Cancel Error: ' . $response->get_error_message());
                } else {
                    $body = json_decode(wp_remote_retrieve_body($response), true);
                    $msg = isset($body['statusMessage']) ? $body['statusMessage'] : 'Unknown response';
                    $order->add_order_note('PostEx Bulk Cancel Response: ' . $msg);
                }
            }
        }
        $redirect_to = add_query_arg('postex_bulk_cancel', count($order_ids), $redirect_to);
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
    return $redirect_to;
}, 8, 3);

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