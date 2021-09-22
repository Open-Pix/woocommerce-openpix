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
        $this->status_when_paid = $this->get_option('status_when_paid');

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

    public function get_order_id_by_correlation_id($correlation_id)
    {
        global $wpdb;

        if (empty($correlation_id)) {
            return false;
        }

        $order_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT DISTINCT ID FROM $wpdb->posts as posts LEFT JOIN $wpdb->postmeta as meta ON posts.ID = meta.post_id WHERE meta.meta_value = %s AND meta.meta_key = %s",
                $correlation_id,
                'openpix_correlation_id'
            )
        );

        if (!empty($order_id)) {
            return $order_id;
        }

        return false;
    }

    public function isPixDetachedPayload($data): bool
    {
        if (!isset($data['pix'])) {
            return false;
        }

        if (isset($data['charge']) && isset($data['charge']['correlationID'])) {
            return false;
        }

        return true;
    }

    public function ipn_handler()
    {
        global $wpdb;
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

        if ($this->isPixDetachedPayload($data)) {
            header('HTTP/1.1 200 OK');

            $response = [
                'message' => 'Pix Detached',
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

        $order_id = $this->get_order_id_by_correlation_id($correlationID);

        if (!$order_id) {
            WC_OpenPix::debug(
                'Cound not find order with correlation ID ' . $correlationID
            );
            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' => 'order not found',
                'order_id' => $order_id,
                'correlationId' => $correlationID,
                'status' => $status,
            ];
            echo json_encode($response);
            exit();
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            WC_OpenPix::debug(
                'Cound not find order with correlation ID ' . $correlationID
            );
            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' => 'order not found',
                'order_id' => $order_id,
                'correlationId' => $correlationID,
                'status' => $status,
            ];
            echo json_encode($response);
            exit();
        }

        $order_correlation_id = get_post_meta(
            $order->id,
            'openpix_correlation_id',
            true
        );
        $order_end_to_end_id = get_post_meta(
            $order->id,
            'openpix_endToEndId',
            true
        );

        if ($order_end_to_end_id) {
            WC_OpenPix::debug('Order already paid ' . $order_id);

            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' => 'order already with end to end id',
                'order_id' => $order_id,
                'correlationId' => $correlationID,
                'status' => $status,
            ];

            echo json_encode($response);
            exit();
        }

        if (!$order_correlation_id) {
            WC_OpenPix::debug('Order without correlation id ' . $order_id);

            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' => 'order without correlation id',
                'order_id' => $order_id,
                'correlationId' => $correlationID,
                'status' => $status,
            ];

            echo json_encode($response);
            exit();
        }

        if ($order_correlation_id !== $correlationID) {
            WC_OpenPix::debug(
                'Order with different correlation id then webhook correlation id ' .
                    $order_id
            );

            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' =>
                    'order with different correlation id ' .
                    $order_correlation_id,
                'order_id' => $order_id,
                'correlationId' => $correlationID,
                'status' => $status,
            ];

            echo json_encode($response);
            exit();
        }

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

                    $order->update_status(
                        $this->status_when_paid,
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
            'status_when_paid' => [
                'title' => __(
                    'Order status after pix charge is paid',
                    'woocommerce-openpix'
                ),
                'type' => 'select',
                'options' => $this->get_available_status(),
                'default' => $this->get_available_status('wc-processing'),
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

    public function formatPhone($phone)
    {
        $phoneSafe = preg_replace('/^0|\D+/', '', $phone);
        if (strlen($phoneSafe) > 11) {
            return $phoneSafe;
        }

        return '55' . $phoneSafe;
    }

    public function getTaxID($order)
    {
        $order_persontype = $order->get_meta('_billing_persontype');
        $order_billing_cpf = $order->get_meta('_billing_cpf');
        $order_billing_cnpj = $order->get_meta('_billing_cnpj');

        if (isset($order_persontype)) {
            if ($order_persontype === '1') {
                return isset($order_billing_cpf)
                    ? sanitize_text_field($order_billing_cpf)
                    : sanitize_text_field($order_billing_cnpj);
            }

            return isset($order_billing_cnpj)
                ? sanitize_text_field($order_billing_cnpj)
                : sanitize_text_field($order_billing_cpf);
        }

        return isset($order_billing_cpf)
            ? sanitize_text_field($order_billing_cpf)
            : sanitize_text_field($order_billing_cnpj);
    }

    public function getCustomerData($order)
    {
        $order_billing_cpf = $order->get_meta('_billing_cpf');
        $order_billing_cnpj = $order->get_meta('_billing_cnpj');

        $hasCustomer = isset($order_billing_cpf) || isset($order_billing_cnpj);

        if (!$hasCustomer) {
            return null;
        }

        $order_data = $order->get_data();

        $order_billing_first_name = $order_data['billing']['first_name'];
        $order_billing_last_name = $order_data['billing']['last_name'];
        $order_billing_email = $order_data['billing']['email'];
        $order_billing_phone = $order_data['billing']['phone'];
        $order_billing_cellphone = $order->get_meta('_billing_cellphone');

        $name =
            sanitize_text_field($order_billing_first_name) .
            ' ' .
            sanitize_text_field($order_billing_last_name);

        $email = sanitize_email($order_billing_email);

        $taxID = $this->getTaxID($order);

        $phone = isset($order_billing_cellphone)
            ? sanitize_text_field($order_billing_cellphone)
            : sanitize_text_field($order_billing_phone);

        $customer = [
            'name' => $name,
            'email' => $email,
            'taxID' => $taxID,
            'phone' => $this->formatPhone($phone),
        ];

        return $customer;
    }

    public function validateOrderFields($order)
    {
        $birthdate = sanitize_text_field(
            $order->get_meta('_billing_birthdate')
        );

        if ($birthdate) {
            $parts = explode('/', $birthdate);

            if (!isset($parts[2])) {
                return __('Invalid Birthdate', 'woocommerce-openpix');
            }
        }

        $order_data = $order->get_data();

        $order_billing_phone = $order_data['billing']['phone'];
        $order_billing_cellphone = $order->get_meta('_billing_cellphone');

        $cellphone = sanitize_text_field($order_billing_cellphone);

        if ($cellphone) {
            $phoneSafe = preg_replace('/^0|\D+/', '', $cellphone);

            if (strlen($phoneSafe) != 11 && strlen($phoneSafe) != 10) {
                return __('Invalid Cell Phone', 'woocommerce-openpix');
            }
        }

        $phone = sanitize_text_field($order_billing_phone);

        if ($phone) {
            $phoneSafe = preg_replace('/^0|\D+/', '', $phone);

            if (strlen($phoneSafe) != 11 && strlen($phoneSafe) != 10) {
                return __('Invalid Phone', 'woocommerce-openpix');
            }
        }

        return null;
    }

    public function process_payment($order_id)
    {
        global $woocommerce;
        $order = wc_get_order($order_id);

        $correlationID = WC_OpenPix::uuid_v4();

        $url = $this->getOpenPixApiUrl() . '/api/openpix/v1/charge';

        $cart_total = $this->get_order_total();
        $total_cents = $this->get_openpix_amount($cart_total);

        // validate fields
        $validationError = $this->validateOrderFields($order);

        if ($validationError) {
            wc_add_notice(
                __(
                    'Order with Error: ' . $validationError,
                    'woocommerce-openpix'
                )
            );
            return [
                'result' => 'fail',
            ];
        }

        $storeName = get_bloginfo('name');

        $payload = [
            'correlationID' => $correlationID,
            'value' => $total_cents,
            'comment' => substr($storeName, 0, 140),
        ];

        $customer = $this->getCustomerData($order);

        if ($customer) {
            $payload['customer'] = $customer;
        }

        $params = [
            'timeout' => 60,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $this->appID,
                'version' => WC_OpenPix::VERSION,
                'platform' => 'WOOCOMMERCE',
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
            wc_add_notice(
                __('Error creating Pix, try again', 'woocommerce-openpix'),
                'error'
            );
            WC_OpenPix::debug(
                'Error creating pix:' .
                    json_encode(
                        $response,
                        JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES |
                            JSON_NUMERIC_CHECK
                    )
            );
            return [
                'result' => 'fail',
            ];
        }

        if ($response['response']['code'] === 401) {
            wc_add_notice(__('Invalid AppID', 'woocommerce-openpix'), 'error');
            WC_OpenPix::debug(
                'Error creating pix:' .
                    json_encode(
                        $response,
                        JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES |
                            JSON_NUMERIC_CHECK
                    )
            );
            return [
                'result' => 'fail',
            ];
        }

        if ($response['response']['code'] !== 200) {
            WC_OpenPix::debug(
                'Error creating pix:' .
                    json_encode(
                        $response,
                        JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES |
                            JSON_NUMERIC_CHECK
                    )
            );
            wc_add_notice(
                __('Error creating Pix, try again', 'woocommerce-openpix'),
                'error'
            );
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
        $correlationID = get_post_meta(
            $order_id,
            'openpix_correlation_id',
            true
        );
        $environment = WC_OpenPix::OPENPIX_ENV;

        wc_get_template(
            'payment-instructions.php',
            [
                'paymentLinkUrl' => $data['paymentLinkUrl'],
                'qrCodeImage' => $data['qrCodeImage'],
                'brCode' => $data['brCode'],
                'correlationID' => $correlationID,
                'environment' => $environment,
                'appID' => $this->appID,
            ],
            WC_OpenPIx::get_templates_path(),
            WC_OpenPIx::get_templates_path()
        );
    }
}
