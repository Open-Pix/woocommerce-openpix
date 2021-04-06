<?php

if (!defined('ABSPATH')) {
    exit();
}

function wc_openpix_assets_url()
{
    return plugin_dir_url(dirname(__FILE__)) . 'assets/';
}

// generate UUID uuid_v4
function uuid_v4()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

        // 32 bits for "time_low"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),

        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,

        // 48 bits for "node"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

class WC_OpenPix_Pix_Gateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'woocommerce_openpix_pix';
        $this->method_title = __('OpenPix', 'woocommerce-openpix');
        $this->method_description = __(
            'WooCommerce OpenPix Payment Gateway',
            'woocommerce-openpix'
        );

        $this->has_fields = true; // direct payment
        $this->supports = ['products'];

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        $this->order_button_text = $this->get_option('order_button_text');
        $this->appID = $this->get_option('appID');
        $this->webhookAuthorization = $this->get_option(
            'webhook_authorization'
        );

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [
            $this,
            'process_admin_options',
        ]);
        add_action('woocommerce_api_wc_openpix_pix_gateway', [
            $this,
            'ipn_handler',
        ]);
        add_action('woocommerce_thankyou_' . $this->id, [
            $this,
            'thankyou_page',
        ]);

        $checkout = $this->get_checkout_js_url();

        debug('checkout');
        debug($checkout);
    }

    public function get_checkout_js_url()
    {
        return plugins_url(
            'assets/js/woo-openpix-dev.js',
            plugin_dir_path(__FILE__)
        );

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

    // move ipn to another file
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
    // ipn end

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
            'description' => [
                'title' => __('Description', 'woocommerce-openpix'),
                'type' => 'text',
                'description' => __(
                    'This controls the description which the user sees during checkout.',
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
        echo wp_kses_post(wpautop(wptexturize($this->description)));
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

    public function process_payment($order_id)
    {
        global $woocommerce;
        $order = wc_get_order($order_id);

        $correlationID = uuid_v4();

        // create charge based on correlation ID
        debug(print_r($_POST));

        $url = $this->getOpenPixApiUrl() . '/api/openpix/v1/charge';

        $cart_total = $this->get_order_total();
        $storeName = get_bloginfo('name');

        $hasCustomer =
            isset($_POST['billing_cpf']) || isset($_POST['billing_cnpj']);
        if ($hasCustomer) {
            $customer = [
                'name' =>
                    $_POST['billing_first_name'] .
                    ' ' .
                    $_POST['billing_last_name'],
                'email' => '',
                'taxID' => isset($_POST['billing_cpf'])
                    ? $_POST['billing_cpf']
                    : $_POST['billing_cnpj'],
                'phone' => isset($_POST['billing_cellphone'])
                    ? $_POST['billing_cellphone']
                    : $_POST['billing_phone'],
            ];
        } else {
            $customer = [];
        }

        $payload = [
            'correlationID' => $correlationID,
            'value' => $cart_total * 100,
            'comment' => $storeName,
        ];

        if ($hasCustomer) {
            $payload['customer'] = $customer;
        }

        $params = [
            'timeout' => 60,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $this->appID,
            ],
            'body' => json_encode($payload),
            'method' => 'POST',
            'data_format' => 'body',
        ];

        debug(print_r($params));

        if (WC_OpenPix::OPENPIX_ENV === 'development') {
            $response = wp_remote_post($url, $params);
        } else {
            $response = wp_safe_remote_post($url, $params);
        }

        if (is_wp_error($response)) {
            wc_add_notice(__('Error creating Pix', 'woocommerce-openpix'));
            return [
                'result' => 'fail',
            ];
        }

        $data = json_decode($response['body'], true);
        debug(print_r($response));

        $meta_data = [
            'openpix_correlation_id' => $correlationID,
            'openpix_transaction' => [
                'paymentLinkUrl' => $data['charge']['paymentLinkUrl'],
                'qrCodeImage' => $data['charge']['qrCodeImage'],
                'brCode' => $data['charge']['brCode'],
            ],
        ];

        // WooCommerce 3.0 or later
        if (!method_exists($order, 'update_meta_data')) {
            foreach ($meta_data as $key => $value) {
                update_post_meta($id, $key, $value);
            }
        } else {
            foreach ($meta_data as $key => $value) {
                $order->update_meta_data($key, $value);
            }

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

        WC()->cart->empty_cart();

        return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }

    public function thankyou_page($order_id)
    {
        $order = wc_get_order($order_id);
        $data = get_post_meta($order_id, 'openpix_transaction', true);

        wc_get_template(
            'payment-instructions.php',
            [
                'paymentLinkUrl' => $data['paymentLinkUrl'],
                'qrCodeImage' => $data['qrCodeImage'],
                'brCode' => $data['brCode'],
            ],
            WC_OpenPIx::get_templates_path(),
            WC_OpenPIx::get_templates_path()
        );
    }
}
