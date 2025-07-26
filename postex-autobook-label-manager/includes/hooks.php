<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('woocommerce_thankyou', function($order_id) {
    if ( ! $order_id ) return;
    $order = wc_get_order($order_id);
    if ( ! $order ) return;

    // Only process if payment method is COD and not already booked
    if ( $order->get_payment_method() !== 'cod' ) return;
    if ( get_post_meta($order_id, '_postex_tracking_number', true) ) return;

    // Get API token and pickup address code
    $api_token = get_option('postex_api_token');
    $pickup_address_code = get_option('postex_pickup_address_code', '001');
    if ( ! $api_token ) return;

    // Get operational cities and find best match
    $operational_cities = function_exists('postex_get_operational_cities') ? postex_get_operational_cities() : [];
    $user_city = $order->get_billing_city();
    $matched_city = function_exists('postex_find_best_city_match') ? postex_find_best_city_match($user_city, $operational_cities) : $user_city;

    // Prepare data for PostEx /create-order
    $billing = [
        'customerName'    => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'customerPhone'   => $order->get_billing_phone(),
        'deliveryAddress' => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
        'cityName'        => $matched_city,
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
        'invoiceDivision' => 1, // default to 1
        'invoicePayment'  => (float) $order->get_total(),
        'items'           => $items_count,
        'orderDetail'     => $order_detail_str,
        'orderRefNumber'  => $order->get_order_number(),
        'orderType'       => 'Normal',
        'transactionNotes'=> '',
        'pickupAddressCode' => $pickup_address_code,
        // 'storeAddressCode'  => '', // Optional
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
        $order->add_order_note('PostEx API Error: ' . $response->get_error_message());
        return;
    }
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ( isset($body['statusCode']) && $body['statusCode'] == '200' && isset($body['dist']['trackingNumber']) ) {
        update_post_meta($order_id, '_postex_tracking_number', $body['dist']['trackingNumber']);
        $order->add_order_note('PostEx Booking Success. Tracking Number: ' . $body['dist']['trackingNumber']);
    } else {
        $msg = isset($body['statusMessage']) ? $body['statusMessage'] : 'Unknown error';
        $order->add_order_note('PostEx Booking Failed: ' . $msg);
    }
}); 