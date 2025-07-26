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

/**
 * Calculate Levenshtein distance between two strings (edit distance)
 */
function postex_levenshtein_distance($str1, $str2) {
    $len1 = strlen($str1);
    $len2 = strlen($str2);
    
    if ($len1 == 0) return $len2;
    if ($len2 == 0) return $len1;
    
    $matrix = array();
    for ($i = 0; $i <= $len1; $i++) {
        $matrix[$i][0] = $i;
    }
    for ($j = 0; $j <= $len2; $j++) {
        $matrix[0][$j] = $j;
    }
    
    for ($i = 1; $i <= $len1; $i++) {
        for ($j = 1; $j <= $len2; $j++) {
            $cost = ($str1[$i-1] == $str2[$j-1]) ? 0 : 1;
            $matrix[$i][$j] = min(
                $matrix[$i-1][$j] + 1,     // deletion
                $matrix[$i][$j-1] + 1,     // insertion
                $matrix[$i-1][$j-1] + $cost // substitution
            );
        }
    }
    
    return $matrix[$len1][$len2];
}

/**
 * Calculate similarity score between two strings (0-100, higher = more similar)
 */
function postex_string_similarity($str1, $str2) {
    $str1 = strtolower(trim($str1));
    $str2 = strtolower(trim($str2));
    
    if ($str1 === $str2) return 100;
    
    $maxLen = max(strlen($str1), strlen($str2));
    if ($maxLen == 0) return 100;
    
    $distance = postex_levenshtein_distance($str1, $str2);
    return round((1 - $distance / $maxLen) * 100, 2);
}

/**
 * Clean and normalize city names for better matching
 */
function postex_normalize_city_name($city) {
    $city = strtolower(trim($city));
    
    // Remove common suffixes/prefixes
    $city = preg_replace('/\b(city|town|district|tehsil)\b/', '', $city);
    
    // Remove extra spaces and special characters
    $city = preg_replace('/[^a-z0-9\s]/', '', $city);
    $city = preg_replace('/\s+/', ' ', $city);
    
    return trim($city);
}

function postex_find_best_city_match($user_city, $operational_cities) {
    if (empty($user_city) || empty($operational_cities)) {
        return !empty($operational_cities) ? $operational_cities[0] : '';
    }
    
    $user_city_normalized = postex_normalize_city_name($user_city);
    $best_match = '';
    $highest_score = 0;
    $similarity_threshold = 60; // Minimum 60% similarity required
    
    // Step 1: Try exact match first (case insensitive)
    foreach ($operational_cities as $city) {
        if (strtolower(trim($city)) === strtolower(trim($user_city))) {
            return $city;
        }
    }
    
    // Step 2: Try substring matches (one contains the other)
    foreach ($operational_cities as $city) {
        $city_normalized = postex_normalize_city_name($city);
        
        // Check if one string contains the other
        if (strpos($user_city_normalized, $city_normalized) !== false || 
            strpos($city_normalized, $user_city_normalized) !== false) {
            return $city;
        }
    }
    
    // Step 3: Calculate similarity scores for all cities
    $scores = array();
    foreach ($operational_cities as $city) {
        $city_normalized = postex_normalize_city_name($city);
        
        // Calculate multiple similarity metrics
        $exact_similarity = postex_string_similarity($user_city_normalized, $city_normalized);
        $start_similarity = postex_string_similarity(substr($user_city_normalized, 0, 4), substr($city_normalized, 0, 4));
        $phonetic_similarity = postex_string_similarity(soundex($user_city_normalized), soundex($city_normalized)) * 0.7; // Weight phonetic less
        
        // Combined score with weights
        $combined_score = ($exact_similarity * 0.7) + ($start_similarity * 0.2) + ($phonetic_similarity * 0.1);
        
        $scores[$city] = $combined_score;
        
        if ($combined_score > $highest_score) {
            $highest_score = $combined_score;
            $best_match = $city;
        }
    }
    
    // Step 4: Only return match if it meets minimum similarity threshold
    if ($highest_score >= $similarity_threshold) {
        return $best_match;
    }
    
    // Step 5: Last resort - try common abbreviations
    $common_abbrevs = [
        'lhr' => 'lahore', 'khi' => 'karachi', 'isb' => 'islamabad',
        'rwp' => 'rawalpindi', 'fsd' => 'faisalabad', 'mtn' => 'multan'
    ];
    
    $user_lower = strtolower(trim($user_city));
    if (isset($common_abbrevs[$user_lower])) {
        $full_name = $common_abbrevs[$user_lower];
        foreach ($operational_cities as $city) {
            if (strpos(strtolower(trim($city)), $full_name) !== false) {
                return $city;
            }
        }
    }
    
    // Fallback: Return the city with highest score, even if below threshold
    return $best_match ?: (!empty($operational_cities) ? $operational_cities[0] : '');
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
    
    // Debug: Show available cities if requested
    if (isset($_GET['debug_cities'])) {
        echo '<div class="notice notice-info"><p><strong>Available PostEx Cities:</strong><br>';
        echo implode(', ', array_slice($cities, 0, 20)) . (count($cities) > 20 ? '... (' . count($cities) . ' total)' : '');
        echo '</p></div>';
    }
    
    echo '<div class="wrap"><h1>' . esc_html__('Review & Edit Orders Before PostEx Booking', 'postex-autobook-label-manager') . '</h1>';
    echo '<p><a href="' . add_query_arg('debug_cities', '1') . '">Show Available Cities</a></p>';
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
        
        // Debug: Show original city and matched city with similarity score
        $best_match = postex_find_best_city_match($city, $cities);
        $debug_info = '';
        if ($city !== $best_match) {
            $similarity = postex_string_similarity(postex_normalize_city_name($city), postex_normalize_city_name($best_match));
            $debug_info = ' (Original: ' . $city . ' → Matched: ' . $best_match . ' | Similarity: ' . $similarity . '%)';
        }
        
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
        echo '<td><select name="orders[' . $order_id . '][cityName]" title="' . esc_attr($debug_info) . '">';
        foreach ($cities as $city_option) {
            $selected = ($city_option === $best_match) ? 'selected' : '';
            echo '<option value="' . esc_attr($city_option) . '" ' . $selected . '>' . esc_html($city_option) . '</option>';
        }
        echo '</select>' . esc_html($debug_info) . '</td>';
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