<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function debug($message)
{
    $logger = wc_get_logger();
    $context = [
        'source' => 'woocommerce_openpix',
    ];
    $logger->debug($message, $context);
}

class WC_OpenPix_Gateway extends WC_Payment_Gateway
{
    const VERSION = '1.0.0';

    public function __construct()
    {
        $this->id = 'woocommerce_openpix';
        $this->method_title = __('OpenPix', 'woocommerce_openpix');
        $this->method_description = __('WooCommerce OpenPix Payment Gateway', 'woocommerce_openpix');

        $this->has_fields = true; // direct payment

        $this->supports = ['products'];

        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title                  = $this->get_option( 'title' );
        $this->order_button_text =  $this->get_option( 'order_button_text' );
        $this->appID = $this->get_option('appID');

        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            [$this, 'process_admin_options']
        );

        // inject openpix react
        add_action('wp_enqueue_scripts', [$this, 'checkout_scripts']);
    }

    public function get_checkout_js_url() {
        if (OPENPIX_ENV === 'development') {
            return plugins_url(
                'build/main.js',
                plugin_dir_path( __FILE__ ));
        }

        if (OPENPIX_ENV === 'staging') {
            return plugins_url(
                'assets/js/woo-openpix-dev.js',
                plugin_dir_path( __FILE__ ));
        }

        // production
        return plugins_url(
            'assets/js/woo-openpix.js',
            plugin_dir_path( __FILE__ ));
    }

    public function checkout_scripts()
    {
        if (is_checkout()) {
            wp_enqueue_script(
                'openpix-checkout',
                $this->get_checkout_js_url(),
                ['jquery', 'jquery-blockui'],
                WC_OpenPix_Gateway::VERSION,
                true
            );

            $name = get_bloginfo('name');

            wp_localize_script('openpix-checkout', 'wcOpenpixParams', [
                'appID' => $this->appID,
                'storeName' => $name,
            ]);
        }
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'woocommerce_openpix'),
                'type' => 'checkbox',
                'label' => __('Enable OpenPix', 'woocommerce_openpix'),
                'default' => 'no',
            ],
            'appID' => [
                'title' => __('AppID OpenPix', 'woocommerce_openpix'),
                'type' => 'text',
                'description' => 'AppID OpenPix',
                'default' => '',
            ],
            'title' => array(
                'title'       => __( 'Title', 'woocommerce_openpix' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce_openpix' ),
                'desc_tip'    => true,
                'default'     => __( 'Pay with Pix', 'woocommerce_openpix' ),
            ),
            'order_button_text' => array(
                'title'       => __( 'Order Button Text', 'woocommerce_openpix' ),
                'type'        => 'text',
                'description' => __( 'This controls the order button payment label.', 'woocommerce_openpix' ),
                'desc_tip'    => true,
                'default'     => __( 'Pay with Pix', 'woocommerce_openpix' ),
            ),
        ];
    }

    public function is_available()
    {
        return parent::is_available() && !empty($this->appID);
    }

    public function payment_fields()
    {
        if ($description = $this->get_description()) {
            echo wp_kses_post(wpautop(wptexturize($description)));
        }

        $cart_total = $this->get_order_total();

        echo '<div id="openpix-checkout-params" ';
        echo 'data-total="' . esc_attr($cart_total * 100) . '" ';
        echo '></div>';
    }

    public function getOpenPixApiUrl() {
        if (OPENPIX_ENV === 'development') {
            return 'http://localhost:5001';
        }

        if (OPENPIX_ENV === 'staging') {
            return 'https://api.openpix.dev';
        }

        // production
        return 'https://api.openpix.com.br';
    }

    public function validate_correlation_id($correlationID) {
        $url = $this->getOpenPixApiUrl() . '/api/openpix/v1/charge/' . $correlationID;
        debug('url: ', $url);
//        $response = wp_safe_remote_get($url);
//
//        $data = json_decode( $response['body'], true );
//
//        debug('correlation get ' . $correlationID);
//        debug(print_r($response));
//        debug(print_r($data));
    }

    public function process_payment($order_id)
    {
        global $woocommerce;

        $order = wc_get_order($order_id);

        $correlationID = $_POST['openpix_correlation_id'];
        $orderNumber = $order->get_order_number();

        debug(print_r($_POST));

        debug(print_r($order));
        debug($order_id);
        debug($orderNumber);

        if (empty($correlationID)) {
            wc_add_notice(__('Missing OpenPix payment information', 'woocommerce_openpix'));
            return [
                'result' => 'fail',
            ];
        }

        // TODO - validate correlationID is valid, and also the charge is paied
        $this->validate_correlation_id($correlationID);

        // WooCommerce 3.0 or later
        if (!method_exists('update_meta_data')) {
            update_post_meta($order_id, 'openpix_correlation_id', $correlationID);
        } else {
            $order->update_meta_data('openpix_correlation_id', $correlationID);

            $order->save();
        }

        if ( ! in_array( $order->get_status(), array( 'processing', 'completed' ), true ) ) {
            $order->add_order_note(__('OpenPix: Transaction paid.', 'woocommerce_openpix'));
        }
        // payment was made using pix instant payment
        $order->payment_complete($correlationID);

        // Empty the cart.
        WC()->cart->empty_cart();
//        $woocommerce->cart->empty_cart();

        return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }
}