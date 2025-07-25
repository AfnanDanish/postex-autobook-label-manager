<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function postex_get_merchant_pickup_address($city = '') {
    $api_token = get_option('postex_api_token');
    if ( ! $api_token ) return false;
    $url = 'https://api.postex.pk/services/integration/api/order/v1/get-merchant-address';
    if ( $city ) {
        $url = add_query_arg('cityName', urlencode($city), $url);
    }
    $response = wp_remote_get($url, [
        'headers' => [
            'token' => $api_token,
        ],
        'timeout' => 20,
    ]);
    if ( is_wp_error($response) ) return false;
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ( isset($body['statusCode']) && $body['statusCode'] == '200' && !empty($body['dist']) ) {
        return $body['dist']; // Array of addresses
    }
    return false;
} 