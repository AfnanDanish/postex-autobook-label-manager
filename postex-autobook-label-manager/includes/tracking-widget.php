<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Shortcode: [postex_tracking_form]
add_shortcode('postex_tracking_form', function($atts) {
    ob_start();
    postex_render_tracking_form();
    return ob_get_clean();
});

// Widget
add_action('widgets_init', function() {
    register_widget('PostEx_Tracking_Widget');
});

class PostEx_Tracking_Widget extends WP_Widget {
    function __construct() {
        parent::__construct(
            'postex_tracking_widget',
            __('PostEx Tracking Widget', 'postex-autobook-label-manager'),
            ['description' => __('Branded PostEx order tracking for customers.', 'postex-autobook-label-manager')]
        );
    }
    function widget($args, $instance) {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        postex_render_tracking_form();
        echo $args['after_widget'];
    }
    function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Track Your Order', 'postex-autobook-label-manager');
        echo '<p><label for="' . esc_attr($this->get_field_id('title')) . '">' . __('Title:') . '</label>';
        echo '<input class="widefat" id="' . esc_attr($this->get_field_id('title')) . '" name="' . esc_attr($this->get_field_name('title')) . '" type="text" value="' . esc_attr($title) . '" /></p>';
    }
    function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = sanitize_text_field($new_instance['title']);
        return $instance;
    }
}

function postex_render_tracking_form() {
    ?>
    <style>
    .postex-tracking-box { max-width: 400px; margin: 0 auto; background: #fff; border: 2px solid #0a4b78; border-radius: 8px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
    .postex-tracking-box h3 { color: #0a4b78; margin-top: 0; }
    .postex-tracking-box input[type="text"] { width: 100%; padding: 8px; margin-bottom: 12px; border-radius: 4px; border: 1px solid #ccc; }
    .postex-tracking-box button { background: #0a4b78; color: #fff; border: none; padding: 10px 18px; border-radius: 4px; cursor: pointer; }
    .postex-tracking-status { margin-top: 18px; }
    .postex-tracking-error { color: #b00; margin-top: 10px; }
    .postex-tracking-history { margin-top: 10px; font-size: 0.95em; }
    </style>
    <div class="postex-tracking-box">
        <h3><?php esc_html_e('Track Your Order', 'postex-autobook-label-manager'); ?></h3>
        <form method="post" action="">
            <input type="text" name="postex_tracking_input" placeholder="<?php esc_attr_e('Order # or Tracking #', 'postex-autobook-label-manager'); ?>" required />
            <button type="submit"><?php esc_html_e('Track', 'postex-autobook-label-manager'); ?></button>
        </form>
        <?php
        if (!empty($_POST['postex_tracking_input'])) {
            $input = sanitize_text_field($_POST['postex_tracking_input']);
            $tracking_number = '';
            // If numeric, treat as order number
            if (ctype_digit($input)) {
                $order = wc_get_order($input);
                if ($order) {
                    $tracking_number = get_post_meta($order->get_id(), '_postex_tracking_number', true);
                    if (!$tracking_number) {
                        echo '<div class="postex-tracking-error">' . esc_html__('No PostEx tracking number found for this order.', 'postex-autobook-label-manager') . '</div>';
                        return;
                    }
                } else {
                    echo '<div class="postex-tracking-error">' . esc_html__('Order not found.', 'postex-autobook-label-manager') . '</div>';
                    return;
                }
            } else {
                $tracking_number = $input;
            }
            // Call PostEx API
            $api_token = get_option('postex_api_token');
            if (!$api_token) {
                echo '<div class="postex-tracking-error">' . esc_html__('Tracking temporarily unavailable. Please try again later.', 'postex-autobook-label-manager') . '</div>';
                return;
            }
            $url = 'https://api.postex.pk/services/integration/api/order/v1/track-order/' . urlencode($tracking_number);
            $response = wp_remote_get($url, [
                'headers' => [ 'token' => $api_token ],
                'timeout' => 20,
            ]);
            if (is_wp_error($response)) {
                echo '<div class="postex-tracking-error">' . esc_html__('Could not connect to tracking service.', 'postex-autobook-label-manager') . '</div>';
                return;
            }
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body['statusCode']) && $body['statusCode'] == '200' && !empty($body['dist'])) {
                $dist = $body['dist'];
                echo '<div class="postex-tracking-status">';
                echo '<strong>' . esc_html__('Status:', 'postex-autobook-label-manager') . '</strong> ' . esc_html($dist['transactionStatus'] ?? 'N/A');
                echo '<br><strong>' . esc_html__('Customer:', 'postex-autobook-label-manager') . '</strong> ' . esc_html($dist['customerName'] ?? '');
                echo '<br><strong>' . esc_html__('Order Ref:', 'postex-autobook-label-manager') . '</strong> ' . esc_html($dist['orderRefNumber'] ?? '');
                echo '<br><strong>' . esc_html__('Tracking #:', 'postex-autobook-label-manager') . '</strong> ' . esc_html($dist['trackingNumber'] ?? $tracking_number);
                echo '<br><strong>' . esc_html__('Amount:', 'postex-autobook-label-manager') . '</strong> ' . esc_html($dist['invoicePayment'] ?? '');
                echo '</div>';
                if (!empty($dist['transactionStatusHistory']) && is_array($dist['transactionStatusHistory'])) {
                    echo '<div class="postex-tracking-history"><strong>' . esc_html__('Order History:', 'postex-autobook-label-manager') . '</strong><ul>';
                    foreach ($dist['transactionStatusHistory'] as $hist) {
                        echo '<li>' . esc_html($hist['transactionStatusMessage'] . ' (' . $hist['transactionStatusMessageCode'] . ')') . '</li>';
                    }
                    echo '</ul></div>';
                }
            } else {
                echo '<div class="postex-tracking-error">' . esc_html__('Tracking info not found. Please check your number and try again.', 'postex-autobook-label-manager') . '</div>';
            }
        }
        ?>
    </div>
    <?php
} 