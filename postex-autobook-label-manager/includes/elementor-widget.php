<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check if Elementor is active
add_action('elementor/widgets/widgets_registered', function() {
    if (did_action('elementor/loaded')) {
        require_once POSTEX_PLUGIN_PATH . 'includes/elementor-widget.php';
    }
});

// Register Elementor widget
add_action('elementor/widgets/widgets_registered', function() {
    if (class_exists('\Elementor\Widget_Base')) {
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \PostEx_Elementor_Tracking_Widget());
    }
});

// Elementor widget class
class PostEx_Elementor_Tracking_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'postex_tracking_widget';
    }

    public function get_title() {
        return __('PostEx Order Tracking', 'postex-autobook-label-manager');
    }

    public function get_icon() {
        return 'eicon-search';
    }

    public function get_categories() {
        return ['basic'];
    }

    public function get_keywords() {
        return ['postex', 'tracking', 'order', 'courier', 'shipping'];
    }

    protected function _register_controls() {
        
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'postex-autobook-label-manager'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'widget_title',
            [
                'label' => __('Title', 'postex-autobook-label-manager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Track Your Order', 'postex-autobook-label-manager'),
                'placeholder' => __('Enter widget title', 'postex-autobook-label-manager'),
            ]
        );

        $this->add_control(
            'placeholder_text',
            [
                'label' => __('Placeholder Text', 'postex-autobook-label-manager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Order # or Tracking #', 'postex-autobook-label-manager'),
                'placeholder' => __('Enter placeholder text', 'postex-autobook-label-manager'),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => __('Button Text', 'postex-autobook-label-manager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Track', 'postex-autobook-label-manager'),
                'placeholder' => __('Enter button text', 'postex-autobook-label-manager'),
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'postex-autobook-label-manager'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'box_background_color',
            [
                'label' => __('Background Color', 'postex-autobook-label-manager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .postex-tracking-box' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'box_border_color',
            [
                'label' => __('Border Color', 'postex-autobook-label-manager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .postex-tracking-box' => 'border-color: {{VALUE}}',
                    '{{WRAPPER}} .postex-tracking-box input[type="text"]' => 'border-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Title Color', 'postex-autobook-label-manager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .postex-tracking-box h3' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'button_background_color',
            [
                'label' => __('Button Background', 'postex-autobook-label-manager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .postex-tracking-box button' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __('Button Text Color', 'postex-autobook-label-manager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .postex-tracking-box button' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_responsive_control(
            'box_width',
            [
                'label' => __('Width', 'postex-autobook-label-manager'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 300,
                        'max' => 800,
                        'step' => 10,
                    ],
                    '%' => [
                        'min' => 50,
                        'max' => 100,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 400,
                ],
                'selectors' => [
                    '{{WRAPPER}} .postex-tracking-box' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'box_padding',
            [
                'label' => __('Padding', 'postex-autobook-label-manager'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 24,
                    'right' => 24,
                    'bottom' => 24,
                    'left' => 24,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .postex-tracking-box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'box_border_radius',
            [
                'label' => __('Border Radius', 'postex-autobook-label-manager'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 8,
                ],
                'selectors' => [
                    '{{WRAPPER}} .postex-tracking-box' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Get values with fallbacks
        $title = !empty($settings['widget_title']) ? $settings['widget_title'] : __('Track Your Order', 'postex-autobook-label-manager');
        $placeholder = !empty($settings['placeholder_text']) ? $settings['placeholder_text'] : __('Order # or Tracking #', 'postex-autobook-label-manager');
        $button_text = !empty($settings['button_text']) ? $settings['button_text'] : __('Track', 'postex-autobook-label-manager');
        
        ?>
        <div class="postex-elementor-tracking-widget">
            <?php 
            // Render the tracking form with custom settings
            postex_render_tracking_form_elementor($title, $placeholder, $button_text); 
            ?>
        </div>
        <?php
    }
}

// Custom function for Elementor widget rendering
function postex_render_tracking_form_elementor($title, $placeholder, $button_text) {
    ?>
    <style>
    .postex-tracking-box { 
        max-width: 400px; 
        margin: 0 auto; 
        background: #ffffff; 
        border: 2px solid #000000; 
        border-radius: 8px; 
        padding: 24px; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    .postex-tracking-box h3 { 
        color: #000000; 
        margin-top: 0; 
        margin-bottom: 16px;
        font-weight: 600;
        text-align: center;
    }
    .postex-tracking-box input[type="text"] { 
        width: 100%; 
        padding: 12px; 
        margin-bottom: 16px; 
        border-radius: 4px; 
        border: 2px solid #000000; 
        font-size: 14px;
        transition: border-color 0.3s ease;
        box-sizing: border-box;
    }
    .postex-tracking-box input[type="text"]:focus {
        outline: none;
        border-color: #333333;
    }
    .postex-tracking-box button { 
        background: #000000; 
        color: #ffffff; 
        border: none; 
        padding: 12px 24px; 
        border-radius: 4px; 
        cursor: pointer; 
        width: 100%;
        font-size: 14px;
        font-weight: 600;
        transition: background-color 0.3s ease;
    }
    .postex-tracking-box button:hover {
        background: #333333;
    }
    .postex-tracking-status { 
        margin-top: 20px; 
        padding: 16px;
        background: #f8f8f8;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
    }
    .postex-tracking-error { 
        color: #000000; 
        margin-top: 16px; 
        padding: 12px;
        background: #f0f0f0;
        border: 1px solid #cccccc;
        border-radius: 4px;
        text-align: center;
    }
    .postex-tracking-history { 
        margin-top: 16px; 
        font-size: 14px;
        border-top: 1px solid #e0e0e0;
        padding-top: 16px;
    }
    .postex-tracking-history ul {
        margin: 8px 0 0 0;
        padding-left: 20px;
    }
    .postex-tracking-history li {
        margin-bottom: 4px;
        color: #333333;
    }
    </style>
    <div class="postex-tracking-box">
        <h3><?php echo esc_html($title); ?></h3>
        <form method="post" action="">
            <input type="text" name="postex_tracking_input" placeholder="<?php echo esc_attr($placeholder); ?>" required />
            <button type="submit"><?php echo esc_html($button_text); ?></button>
        </form>
        <?php
        // Same tracking logic as the original widget
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
            if (isset($body['statusCode']) && $body['statusCode'] == '200') {
                // According to API docs, data is directly in the response, not in 'dist'
                echo '<div class="postex-tracking-status">';
                echo '<strong>' . esc_html__('Status:', 'postex-autobook-label-manager') . '</strong> ' . esc_html($body['transactionStatus'] ?? 'N/A');
                echo '<br><strong>' . esc_html__('Customer:', 'postex-autobook-label-manager') . '</strong> ' . esc_html($body['customerName'] ?? '');
                echo '<br><strong>' . esc_html__('Order Ref:', 'postex-autobook-label-manager') . '</strong> ' . esc_html($body['orderRefNumber'] ?? '');
                echo '<br><strong>' . esc_html__('Tracking #:', 'postex-autobook-label-manager') . '</strong> ' . esc_html($body['trackingNumber'] ?? $tracking_number);
                echo '<br><strong>' . esc_html__('Amount:', 'postex-autobook-label-manager') . '</strong> ' . esc_html($body['invoicePayment'] ?? '');
                if (!empty($body['deliveryAddress'])) {
                    echo '<br><strong>' . esc_html__('Address:', 'postex-autobook-label-manager') . '</strong> ' . esc_html($body['deliveryAddress']);
                }
                if (!empty($body['cityName'])) {
                    echo '<br><strong>' . esc_html__('City:', 'postex-autobook-label-manager') . '</strong> ' . esc_html($body['cityName']);
                }
                echo '</div>';
                
                // Show transaction status history if available
                if (!empty($body['transactionStatusHistory']) && is_array($body['transactionStatusHistory'])) {
                    echo '<div class="postex-tracking-history"><strong>' . esc_html__('Order History:', 'postex-autobook-label-manager') . '</strong><ul>';
                    foreach ($body['transactionStatusHistory'] as $hist) {
                        if (isset($hist['transactionStatusMessage'])) {
                            echo '<li>' . esc_html($hist['transactionStatusMessage']);
                            if (isset($hist['transactionStatusMessageCode'])) {
                                echo ' (' . esc_html($hist['transactionStatusMessageCode']) . ')';
                            }
                            echo '</li>';
                        }
                    }
                    echo '</ul></div>';
                }
            } else {
                $error_msg = isset($body['statusMessage']) ? $body['statusMessage'] : 'Tracking info not found. Please check your number and try again.';
                echo '<div class="postex-tracking-error">' . esc_html__($error_msg, 'postex-autobook-label-manager') . '</div>';
            }
        }
        ?>
    </div>
    <?php
}
