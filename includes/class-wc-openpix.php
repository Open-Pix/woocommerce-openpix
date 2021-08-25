<?php

if (!defined('ABSPATH')) {
    exit();
}

// experimental plugin that pay before creating order
class WC_OpenPix_Gateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'woocommerce_openpix';
        $this->method_title = __('OpenPix', 'woocommerce-openpix');
        $this->method_description = __(
            'WooCommerce OpenPix Payment Gateway',
            'woocommerce-openpix'
        );

        $this->has_fields = true; // direct payment

        $this->supports = ['products'];

        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->order_button_text = $this->get_option('order_button_text');
        $this->appID = $this->get_option('appID');
        $this->webhookAuthorization = $this->get_option(
            'webhook_authorization'
        );

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [
            $this,
            'process_admin_options',
        ]);

        // inject openpix react
        add_action('wp_enqueue_scripts', [$this, 'checkout_scripts']);
        add_action('woocommerce_api_wc_openpix_gateway', [
            $this,
            'ipn_handler',
        ]);
    }

    public function isValidTestWebhookPayload($data)
    {
        if (isset($data['evento'])) {
            return true;
        }

        return false;
    }

    public function isValidWebhookPayload($data)
    {
        if (!isset($data['charge'])) {
            return false;
        }

        if (!isset($data['pix'])) {
            return false;
        }

        return true;
    }

    public function getAuthorization()
    {
        if (array_key_exists('HTTP_AUTHORIZATION', $_SERVER)) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (array_key_exists('Authorization', $_SERVER)) {
            return $_SERVER['Authorization'];
        }

        return '';
    }

    public function ipn_handler()
    {
        @ob_clean();

        $body = file_get_contents('php://input', true);
        $data = json_decode($body, true);

        $authorization = $this->getAuthorization();

        if ($authorization !== $this->webhookAuthorization) {
            header('HTTP/1.2 400 Bad Request');
            $response = [
                'error' => 'Invalid Webhook Authorization',
            ];
            echo json_encode($response);
            exit();
        }

        if ($this->isValidTestWebhookPayload($data)) {
            header('HTTP/1.1 200 OK');

            $response = [
                'message' => 'success',
            ];
            echo json_encode($response);
            exit();
        }

        if (!$this->isValidWebhookPayload($data)) {
            header('HTTP/1.2 400 Bad Request');
            $response = [
                'error' => 'Invalid Webhook Payload',
            ];
            echo json_encode($response);
            exit();
        }

        global $wpdb;
        $correlationID = $data['charge']['correlationID'];
        $status = $data['charge']['status'];

        $orders = wc_get_orders([
            'openpix_correlation_id' => $correlationID,
        ]);

        if (count($orders) === 0) {
            header('HTTP/1.2 400 Bad Request');
            $response = [
                'error' => 'Order not found',
            ];
            echo json_encode($response);
            exit();
        }

        $order = $orders[0];
        $order_id = $order->id;

        if ($order) {
            if ($status === 'COMPLETED') {
                if (
                    !in_array(
                        $order->get_status(),
                        ['processing', 'completed'],
                        true
                    )
                ) {
                    $order->add_order_note(
                        __('OpenPix: Transaction paid', 'woocommerce-openpix')
                    );
                }

                // Changing the order for processing and reduces the stock.
                $order->payment_complete();
            }
        }

        header('HTTP/1.1 200 OK');

        $response = [
            'message' => 'success',
            'order_id' => $order_id,
            'correlationId' => $correlationID,
            'status' => $status,
        ];

        echo json_encode($response);
        exit();
    }

    public function get_checkout_js_url()
    {
        if (WC_OpenPix::OPENPIX_ENV === 'development') {
            return plugins_url('build/main.js', plugin_dir_path(__FILE__));
        }

        if (WC_OpenPix::OPENPIX_ENV === 'staging') {
            return plugins_url(
                'assets/js/woo-openpix-dev.js',
                plugin_dir_path(__FILE__)
            );
        }

        // production
        return plugins_url(
            'assets/js/woo-openpix.js',
            plugin_dir_path(__FILE__)
        );
    }

    public function checkout_scripts()
    {
        if (is_checkout()) {
            wp_enqueue_script(
                'openpix-checkout',
                $this->get_checkout_js_url(),
                ['jquery', 'jquery-blockui'],
                WC_OpenPix::VERSION,
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
                'title' => __('Enable/Disable', 'woocommerce-openpix'),
                'type' => 'checkbox',
                'label' => __('Enable OpenPix', 'woocommerce-openpix'),
                'default' => 'no',
            ],
            'appID' => [
                'title' => __('AppID OpenPix', 'woocommerce-openpix'),
                'type' => 'text',
                'description' => 'AppID OpenPix',
                'default' => '',
            ],
            'title' => [
                'title' => __('Title', 'woocommerce-openpix'),
                'type' => 'text',
                'description' => __(
                    'This controls the title which the user sees during checkout.',
                    'woocommerce-openpix'
                ),
                'desc_tip' => true,
                'default' => __('Pay with Pix', 'woocommerce-openpix'),
            ],
            'order_button_text' => [
                'title' => __('Order Button Text', 'woocommerce-openpix'),
                'type' => 'text',
                'description' => __(
                    'This controls the order button payment label.',
                    'woocommerce-openpix'
                ),
                'desc_tip' => true,
                'default' => __('Pay with Pix', 'woocommerce-openpix'),
            ],
            'webhook_authorization' => [
                'title' => __('Webhook Authorization', 'woocommerce-openpix'),
                'type' => 'text',
                'description' => __(
                    'This will be used to validate Webhook/IPN request calls to approve payments',
                    'woocommerce-openpix'
                ),
                'desc_tip' => true,
                'default' => '',
            ],
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

    public function getOpenPixApiUrl()
    {
        if (WC_OpenPix::OPENPIX_ENV === 'development') {
            return 'http://localhost:5001';
        }

        if (WC_OpenPix::OPENPIX_ENV === 'staging') {
            return 'https://api.openpix.dev';
        }

        // production
        return 'https://api.openpix.com.br';
    }

    public function validate_correlation_id($correlationID)
    {
        $url =
            $this->getOpenPixApiUrl() .
            '/api/openpix/v1/charge/' .
            $correlationID;

        $params = [
            'timeout' => 60,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $this->appID,
                'version' => WC_OpenPix::VERSION,
                'platform' => 'WOOCOMMERCE',
            ],
        ];

        if (WC_OpenPix::OPENPIX_ENV === 'development') {
            $response = wp_remote_get($url, $params);
        } else {
            $response = wp_safe_remote_get($url, $params);
        }
        //        $body = wp_remote_retrieve_body($response);
        $data = json_decode($response['body'], true);

        // check if correlationID is valid, check if the payment was paid
    }

    public function process_payment($order_id)
    {
        global $woocommerce;

        $order = wc_get_order($order_id);

        $correlationID = $_POST['openpix_correlation_id'];
        $orderNumber = $order->get_order_number();

        if (empty($correlationID)) {
            wc_add_notice(
                __('Missing OpenPix payment information', 'woocommerce-openpix')
            );
            return [
                'result' => 'fail',
            ];
        }

        // TODO - validate correlationID is valid, and also the charge is paied
        $this->validate_correlation_id($correlationID);

        // WooCommerce 3.0 or later
        if (!method_exists('update_meta_data')) {
            update_post_meta(
                $order_id,
                'openpix_correlation_id',
                $correlationID
            );
        } else {
            $order->update_meta_data('openpix_correlation_id', $correlationID);

            $order->save();
        }

        // let transaction in hold
        $order->update_status(
            'on-hold',
            __(
                'OpenPix: The Pix was emitted but not paied yet.',
                'woocommerce-openpix'
            )
        );

        // Empty the cart.
        WC()->cart->empty_cart();
        //        $woocommerce->cart->empty_cart();

        return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }
}
