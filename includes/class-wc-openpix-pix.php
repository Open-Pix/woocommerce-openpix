<?php

if (!defined('ABSPATH')) {
    exit();
}

add_action('admin_footer', 'embedWebhookConfigButton');

function embedWebhookConfigButton()
{
    ?>
    
	<script type="text/javascript" >
	jQuery(document).ready(function($) {

        jQuery("#woocommerce_woocommerce_openpix_pix_webhook_button").click(() => {
            var data = {
                action: 'openpix_configure_webhook',
                appID: jQuery('#woocommerce_woocommerce_openpix_pix_appID').val()
            };
            jQuery.post(ajaxurl,data,function(response) {
                if(response?.message) {
                    alert(response.message);
                }
                if(response?.success) {
                    if(response?.body?.webhook_authorization) {
                        jQuery("#woocommerce_woocommerce_openpix_pix_webhook_authorization").val(response.body.webhook_authorization);
                    }
                    if(response?.body?.hmac_authorization) {
                        jQuery("#woocommerce_woocommerce_openpix_pix_hmac_authorization").val(response.body.hmac_authorization);
                    }
                    if(response?.body?.webhook_status) {
                        jQuery("#woocommerce_woocommerce_openpix_pix_webhook_status").val(response.body.webhook_status);
                    }
                }
            })
        })
	});
	</script> <?php
}
add_action('wp_ajax_openpix_configure_webhook', [
    'WC_OpenPix_Pix_Gateway',
    'openpix_configure_webhook',
]);
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

        $this->realtime = $this->get_option('realtime') === 'yes';

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
    /**
     * Validate the webhook for security reasons.
     *
     * @return bool
     */
    public function validateRequest()
    {
        $systemWebhookAuthorization = $this->webhookAuthorization;

        $webhookAuthHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $webhookAuthOpenPixHeader =
            $_SERVER['HTTP_X_OPENPIX_AUTHORIZATION'] ?? '';
        $webhookAuthQueryString = $_GET['authorization'] ?? '';

        $isAuthHeaderValid = $webhookAuthHeader === $systemWebhookAuthorization;
        $isAuthOpenPixHeaderValid =
            $webhookAuthOpenPixHeader === $systemWebhookAuthorization;
        $isAuthQueryStringValid =
            $webhookAuthQueryString === $systemWebhookAuthorization;

        return $isAuthHeaderValid ||
            $isAuthOpenPixHeaderValid ||
            $isAuthQueryStringValid;
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

        if (!$this->validateRequest()) {
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

                    $order->update_status(
                        $this->status_when_paid,
                        __('OpenPix: Transaction paid', 'woocommerce-openpix')
                    );

                    $order->payment_complete();

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
        $webhookUrl = self::getWebhookUrl();

        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'woocommerce-openpix'),
                'type' => 'checkbox',
                'label' => __('Enable OpenPix', 'woocommerce-openpix'),
                'default' => 'no',
            ],
            'realtime' => [
                'title' => __('Update UI in realtime', 'woocommerce-openpix'),
                'type' => 'checkbox',
                'label' => __('Enable realtime', 'woocommerce-openpix'),
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
            'webhook_button' => [
                'type' => 'button',
                'title' => __('One Click Configuration', 'woocommerce-openpix'),
                'class' => 'button-primary',
                'description' => __(
                    'Configure webhook on your site with OpenPix in one click',
                    'woocommerce-openpix'
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
                'custom_attributes' => [
                    'readonly' => 'readonly',
                ],
                'default' => '',
            ],
            'hmac_authorization' => [
                'type' => 'text',
                'title' => __('Webhook HMAC Secret Key', 'woocommerce-openpix'),
                'description' => __('Hmac signature', 'woocommerce-openpix'),
                'custom_attributes' => [
                    'readonly' => 'readonly',
                ],
            ],
            'webhook_status' => [
                'type' => 'text',
                'title' => __('Webhook Status', 'woocommerce-openpix'),
                'description' => __('Status ', 'woocommerce-openpix'),
                'custom_attributes' => [
                    'readonly' => 'readonly',
                ],
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

        if (!$this->get_option('webhook_button')) {
            $this->update_option(
                'webhook_button',
                __('Configure now with one click', 'woocommerce-openpix')
            );
        }

        if (!$this->get_option('webhook_status')) {
            $this->update_option(
                'webhook_status',
                __('Not configured', 'woocommerce-openpix')
            );
        }
    }

    public function is_available()
    {
        return parent::is_available() && !empty($this->appID);
    }

    public function payment_fields()
    {
        echo wp_kses_post(wpautop(wptexturize($this->description)));
    }

    public static function getOpenPixApiUrl()
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

        $payload = $this->getPayload(
            $order_id,
            $correlationID,
            $total_cents,
            $order
        );

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
                'pixKey' => $data['charge']['pixKey'],
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
    public static function getWebhookUrl()
    {
        if (WC_OpenPix::OPENPIX_ENV == 'development') {
            $webhookUrl = str_replace(
                'https:',
                'http:',
                home_url('/') . 'wc-api/' . 'WC_OpenPix_Pix_Gateway'
            );
            return $webhookUrl;
        }
        return home_url('/') . 'wc-api/' . 'WC_OpenPix_Pix_Gateway';
    }
    public static function openpix_configure_webhook()
    {
        $webhookUrl = self::getWebhookUrl();

        $url = self::getOpenPixApiUrl() . '/api/openpix/v1/webhook';
        $openpixSettings = get_option(
            'woocommerce_woocommerce_openpix_pix_settings'
        );
        if (
            empty(trim($openpixSettings['appID'])) &&
            !empty(trim($_POST['appID']))
        ) {
            $openpixSettings['appID'] = trim($_POST['appID']);
            update_option(
                'woocommerce_woocommerce_openpix_pix_settings',
                $openpixSettings
            );
        }
        $appID = $openpixSettings['appID'];

        if (!$appID) {
            $response = [
                'message' => __(
                    'OpenPix: You need to add appID before configuring webhook.',
                    'woocommerce-openpix'
                ),
                'success' => false,
            ];
            wp_send_json($response);
            wp_die();
        }
        $params = [
            'timeout' => 60,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $appID,
                'version' => WC_OpenPix::VERSION,
                'platform' => 'WOOCOMMERCE',
            ],
            'method' => 'GET',
        ];
        $response = wp_remote_get("$url?url=$webhookUrl", $params); // check if alredy have one webhook with this $webhookUrl

        $data = json_decode($response['body'], true);

        $hasActiveWebhook = false;
        foreach ($data['webhooks'] as $webhook) {
            if ($webhook['isActive']) {
                $hasActiveWebhook = true;
                break;
            }
        }
        if ($hasActiveWebhook) {
            if (isset($webhook['authorization'])) {
                $openpixSettings['webhook_authorization'] =
                    $webhook['authorization'];
            }
            if (isset($webhook['hmacSecretKey'])) {
                $openpixSettings['hmac_authorization'] =
                    $webhook['hmacSecretKey'];
            }
            $openpixSettings['webhook_status'] = __(
                'Configured',
                'woocommerce-openpix'
            );

            update_option(
                'woocommerce_woocommerce_openpix_pix_settings',
                $openpixSettings
            );
            $responsePayload = [
                'message' => __(
                    'OpenPix: Webhook already configured.',
                    'woocommerce-openpix'
                ),
                'body' => [
                    'webhook_authorization' =>
                        $openpixSettings['webhook_authorization'],
                    'hmac_authorization' =>
                        $openpixSettings['hmac_authorization'],
                    'webhook_status' => $openpixSettings['webhook_status'],
                ],
                'success' => true,
            ];
            wp_send_json(
                $responsePayload,
                null,
                JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES |
                    JSON_NUMERIC_CHECK
            );

            wp_die();
        }

        $webhookAuthorization = WC_OpenPix::uuid_v4();

        $payload = [
            'webhook' => [
                'name' => 'WooCommerce-Webhook',
                'url' => $webhookUrl,
                'authorization' => $webhookAuthorization,
                'isActive' => true,
            ],
        ];
        $paramsWebhookPost = [
            'timeout' => 60,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $appID,
                'version' => WC_OpenPix::VERSION,
                'platform' => 'WOOCOMMERCE',
            ],
            'body' => json_encode($payload),
            'method' => 'POST',
            'data_format' => 'body',
        ];
        $oldOpenpixSettings = $openpixSettings;
        // because iph_handler validade request
        $openpixSettings['webhook_authorization'] = $webhookAuthorization;

        update_option(
            'woocommerce_woocommerce_openpix_pix_settings',
            $openpixSettings
        );

        $responseWebhookPost = wp_remote_post($url, $paramsWebhookPost);

        $bodyWebhook = json_decode($responseWebhookPost['body'], true);

        if (isset($bodyWebhook['error']) || isset($bodyWebhook['errors'])) {
            // Roolback of openpixSettings
            $openpixSettings['webhook_status'] = __(
                'Not configured',
                'woocommerce-openpix'
            );

            update_option(
                'woocommerce_woocommerce_openpix_pix_settings',
                $oldOpenpixSettings
            );
            $errorFromApi =
                $bodyWebhook['error'] ?? $bodyWebhook['errors'][0]['message'];

            $responsePayload = [
                'message' =>
                    __(
                        'OpenPix: Error configuring webhook.',
                        'woocommerce-openpix'
                    ) . " \n$errorFromApi",
                'body' => [
                    'webhook_status' => $openpixSettings['webhook_status'],
                ],
                'success' => false,
            ];
            wp_send_json(
                $responsePayload,
                null,
                JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES |
                    JSON_NUMERIC_CHECK
            );
            wp_die();
        }

        $openpixSettings['hmac_authorization'] =
            $bodyWebhook['webhook']['hmacSecretKey'];

        $openpixSettings['webhook_status'] = __(
            'Configured',
            'woocommerce-openpix'
        );

        update_option(
            'woocommerce_woocommerce_openpix_pix_settings',
            $openpixSettings
        );

        $responsePayload = [
            'message' => __(
                'OpenPix: Webhook configured.',
                'woocommerce-openpix'
            ),
            'body' => [
                'webhook_authorization' =>
                    $openpixSettings['webhook_authorization'],
                'hmac_authorization' => $openpixSettings['hmac_authorization'],
                'webhook_status' => $openpixSettings['webhook_status'],
            ],
            'success' => true,
        ];
        wp_send_json(
            $responsePayload,
            null,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK
        );
        wp_die(); // this is required to terminate immediately and return a proper response
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
                'pluginUrl' => WC_OpenPix::get_assets_url(),
                'realtime' => $this->realtime,
            ],
            WC_OpenPIx::get_templates_path(),
            WC_OpenPIx::get_templates_path()
        );
    }

    /**
     * @param int $order_id
     * @param string $correlationID
     * @param int $total_cents
     * @param $order
     * @return array
     */
    public function getPayload(
        int $order_id,
        string $correlationID,
        int $total_cents,
        $order
    ): array {
        $storeName = get_bloginfo('name');

        $additionalInformation = [
            [
                'key' => __('Order'),
                'value' => $order_id,
            ],
        ];
        $comment = substr("$storeName", 0, 100) . '#' . $order_id;
        $comment_trimmed = substr($comment, 0, 140);
        $payload = [
            'correlationID' => $correlationID,
            'value' => $total_cents,
            'comment' => $comment_trimmed,
            'additionalInfo' => $additionalInformation,
        ];

        $customer = $this->getCustomerData($order);

        if ($customer) {
            $payload['customer'] = $customer;
        }
        return $payload;
    }
}
