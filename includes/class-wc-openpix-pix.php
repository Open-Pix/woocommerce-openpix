<?php

if (!defined('ABSPATH')) {
    exit();
}

function wc_openpix_assets_url()
{
    return plugin_dir_url(dirname(__FILE__)) . 'assets/';
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

        $this->status_when_waiting = $this->get_option('status_when_waiting');

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
        $endToEndId = $data['pix']['endToEndId'];

        $settings = get_option('woocommerce_openpix_settings');

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
        $order_status = $order->get_status();

        $statuses =
            strpos($order_status, 'wc-') === false
                ? ['processing', 'completed']
                : ['wc-processing', 'wc-completed'];
        $already_paid = in_array($order_status, $statuses) ? true : false;

        WC_OpenPix::debug('ipn');
        WC_OpenPix::debug('already paid ' . $already_paid ? 'yes' : 'no');
        WC_OpenPix::debug('status ' . $status);
        WC_OpenPix::debug('correlationID ' . $correlationID);
        WC_OpenPix::debug('endToEndId ' . $endToEndId);

        if (!$already_paid) {
            if ($order) {
                if ($status === 'COMPLETED') {
                    // Changing the order for processing and reduces the stock.
                    $order->payment_complete();

                    $order->add_order_note(
                        __('OpenPix: Transaction paid', 'woocommerce-openpix')
                    );

                    // add endToEndId to meta data order
                    $meta_data = [
                        'openpix_endToEndId' => $endToEndId,
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
                }
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

    /**
     * Get a list of Woocommerce status available at the installation
     *
     * @return array List of status
     */
    public function get_available_status($needle = null)
    {
        $order_statuses = wc_get_order_statuses();
        if ($needle) {
            foreach ($order_statuses as $key => $value) {
                if (strpos($key, $needle) !== false) {
                    return $key;
                }
            }
        }
        return $needle
            ? array_shift(array_filter($order_statuses, $needle))
            : $order_statuses;
    }

    public function init_form_fields()
    {
        $webhookUrl = str_replace(
            'https:',
            'http:',
            home_url('/') . 'wc-api/' . 'WC_OpenPix_Pix_Gateway'
        );

        //        https://developers.openpix.com.br/docs/apis/api-getting-started
        // https://developers.openpix.com.br/docs/ecommerce/woocommerce-plugin/

        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'woocommerce-openpix'),
                'type' => 'checkbox',
                'label' => __('Enable OpenPix', 'woocommerce-openpix'),
                'default' => 'no',
            ],
            'api_section' => [
                'title' => __('OpenPix Integration API', 'woocommerce-openpix'),
                'type' => 'title',
                'description' => sprintf(
                    __(
                        'Follow documentation to get your OpenPix AppID here %s.',
                        'woocommerce-openpix'
                    ),
                    '<a target="_blank" href="https://developers.openpix.com.br/docs/apis/api-getting-started/">' .
                        __(
                            'OpenPix API Getting Started',
                            'woocommerce-openpix'
                        ) .
                        '</a>'
                ),
            ],
            'appID' => [
                'title' => __('AppID OpenPix', 'woocommerce-openpix'),
                'type' => 'text',
                'description' => 'AppID OpenPix',
                'default' => '',
            ],
            'label_section' => [
                'title' => __('Configure labels', 'woocommerce-openpix'),
                'type' => 'title',
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
            'webhook_section' => [
                'title' => __(
                    'Configure Webhook integration',
                    'woocommerce-openpix'
                ),
                'type' => 'title',
                'description' => sprintf(
                    __(
                        'Follow documentation to configure Webhook on OpenPix here %s.',
                        'woocommerce-openpix'
                    ),
                    '<a target="_blank" href="https://developers.openpix.com.br/docs/ecommerce/woocommerce-plugin/">' .
                        __(
                            'Woocommerce Plugin Documentation',
                            'woocommerce-openpix'
                        ) .
                        '</a>'
                ),
            ],
            'webhook_authorization' => [
                'title' => __('Webhook Authorization', 'woocommerce-openpix'),
                'type' => 'text',
                'description' => sprintf(
                    __(
                        'This will be used to validate Webhook/IPN request calls to approve payments. WooCommerce Webhook URL to be registered at OpenPix: %s',
                        'woocommerce-openpix'
                    ),
                    '<a target="_blank" href="' .
                        $webhookUrl .
                        '">' .
                        $webhookUrl .
                        '</a>'
                ),
                'default' => '',
            ],
            'status_section' => [
                'title' => __('Configure order status', 'woocommerce-openpix'),
                'type' => 'title',
            ],
            'status_when_waiting' => [
                'title' => __(
                    'Change status after issuing the pix to',
                    'woocommerce-openpix'
                ),
                'type' => 'select',
                'options' => $this->get_available_status(),
                'default' => $this->get_available_status('wc-pending'),
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

    public function get_openpix_amount($total)
    {
        return absint(
            wc_format_decimal((float) $total * 100, wc_get_price_decimals())
        ); // In cents.
    }

    public function process_payment($order_id)
    {
        global $woocommerce;
        $order = wc_get_order($order_id);

        $correlationID = WC_OpenPix::uuid_v4();

        $url = $this->getOpenPixApiUrl() . '/api/openpix/v1/charge';

        $cart_total = $this->get_order_total();
        $total_cents = $this->get_openpix_amount($cart_total);

        $hasCustomer =
            isset($_POST['billing_cpf']) || isset($_POST['billing_cnpj']);

        if ($hasCustomer) {
            $customer = [
                'name' =>
                    sanitize_text_field($_POST['billing_first_name']) .
                    ' ' .
                    sanitize_text_field($_POST['billing_last_name']),
                'email' => sanitize_email($_POST['billing_email']),
                'taxID' => isset($_POST['billing_cpf'])
                    ? sanitize_text_field($_POST['billing_cpf'])
                    : sanitize_text_field($_POST['billing_cnpj']),
                'phone' => isset($_POST['billing_cellphone'])
                    ? sanitize_text_field($_POST['billing_cellphone'])
                    : sanitize_text_field($_POST['billing_phone']),
            ];
        } else {
            $customer = [];
        }

        $storeName = get_bloginfo('name');

        $payload = [
            'correlationID' => $correlationID,
            'value' => $total_cents,
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

        if ($response['response']['code'] === 401) {
            wc_add_notice(__('Invalid AppID', 'woocommerce-openpix'));
            return [
                'result' => 'fail',
            ];
        }

        if ($response['response']['code'] !== 200) {
            wc_add_notice(__('Error creating Pix', 'woocommerce-openpix'));
            wc_add_notice($response['body']);
            return [
                'result' => 'fail',
            ];
        }

        $data = json_decode($response['body'], true);

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

        WC_OpenPix::debug('correlationID ' . $correlationID);

        $order->update_status(
            $this->status_when_waiting,
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
