<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('add_meta_boxes', function() {
    add_meta_box(
        'postex_order_metabox',
        __('PostEx Order Manager', 'postex-autobook-label-manager'),
        'postex_render_order_metabox',
        'shop_order',
        'side',
        'default'
    );
});

function postex_render_order_metabox($post) {
    $order_id = $post->ID;
    $tracking_number = get_post_meta($order_id, '_postex_tracking_number', true);
    $api_token = get_option('postex_api_token');
    echo '<div id="postex-order-box">';
    if ( $tracking_number ) {
        echo '<p><strong>' . esc_html__('Tracking Number:', 'postex-autobook-label-manager') . '</strong> ' . esc_html($tracking_number) . '</p>';
        // Track order status
        if ( $api_token ) {
            $response = wp_remote_get('https://api.postex.pk/services/integration/api/order/v1/track-order/' . urlencode($tracking_number), [
                'headers' => [
                    'token' => $api_token,
                ],
                'timeout' => 20,
            ]);
            if ( ! is_wp_error($response) ) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if ( isset($body['dist']['transactionStatus']) ) {
                    $status = esc_html($body['dist']['transactionStatus']);
                    echo '<div style="margin:10px 0;padding:10px;border:1px solid #111;background:#fafafa;">';
                    echo '<strong>' . esc_html__('Booking Status:', 'postex-autobook-label-manager') . '</strong> ' . $status . '<br>';
                    if (!empty($body['dist']['transactionStatusHistory']) && is_array($body['dist']['transactionStatusHistory'])) {
                        echo '<div style="margin-top:8px;"><strong>' . esc_html__('Tracking Timeline:', 'postex-autobook-label-manager') . '</strong><ul style="margin:0 0 0 18px;">';
                        foreach ($body['dist']['transactionStatusHistory'] as $hist) {
                            echo '<li>' . esc_html($hist['transactionStatusMessage']) . ' <span style="color:#888;">(' . esc_html($hist['transactionStatusMessageCode']) . ')</span></li>';
                        }
                        echo '</ul></div>';
                    }
                    echo '</div>';
                }
            }
        }
        // Cancel booking button
        echo '<form method="post">';
        wp_nonce_field('postex_cancel_order_' . $order_id, 'postex_cancel_nonce');
        echo '<input type="hidden" name="postex_cancel_order_id" value="' . esc_attr($order_id) . '" />';
        echo '<button type="submit" class="button" name="postex_cancel_order">' . esc_html__('Cancel Booking', 'postex-autobook-label-manager') . '</button>';
        echo '</form>';
        // Download airway bill button
        $download_url = 'https://api.postex.pk/services/integration/api/order/v1/get-invoice?trackingNumbers=' . urlencode($tracking_number);
        echo '<p><a href="' . esc_url($download_url) . '" class="button" target="_blank">' . esc_html__('Download Airway Bill', 'postex-autobook-label-manager') . '</a></p>';
    } else {
        echo '<p>' . esc_html__('No PostEx booking found for this order.', 'postex-autobook-label-manager') . '</p>';
    }
    echo '</div>';
}

add_action('admin_init', function() {
    if ( isset($_POST['postex_cancel_order']) && isset($_POST['postex_cancel_order_id']) ) {
        $order_id = intval($_POST['postex_cancel_order_id']);
        if ( ! current_user_can('edit_shop_orders', $order_id) ) return;
        if ( ! isset($_POST['postex_cancel_nonce']) || ! wp_verify_nonce($_POST['postex_cancel_nonce'], 'postex_cancel_order_' . $order_id) ) return;
        $tracking_number = get_post_meta($order_id, '_postex_tracking_number', true);
        $api_token = get_option('postex_api_token');
        if ( $tracking_number && $api_token ) {
            $response = wp_remote_request('https://api.postex.pk/services/integration/api/order/v1/cancel-order', [
                'method' => 'PUT',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'token' => $api_token,
                ],
                'body' => wp_json_encode(['trackingNumber' => $tracking_number]),
                'timeout' => 20,
            ]);
            $order = wc_get_order($order_id);
            if ( is_wp_error($response) ) {
                $order->add_order_note('PostEx Cancel Error: ' . $response->get_error_message());
            } else {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $msg = isset($body['statusMessage']) ? $body['statusMessage'] : 'Unknown response';
                $order->add_order_note('PostEx Cancel Response: ' . $msg);
            }
        }
    }
}); 