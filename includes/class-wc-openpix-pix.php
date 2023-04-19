<?php

if (!defined('ABSPATH')) {
    exit();
}

require_once 'config/config.php';

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
    public $appID;
    public $status_when_waiting;
    public $status_when_paid;

    public function __construct()
    {
        $this->id = 'woocommerce_openpix_pix';
        $this->method_title = __('OpenPix', 'woocommerce-openpix');
        $this->method_description = __(
            'Accept Pix payments with real-time updates, seamless checkout, and automatic order status updates.',
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

        // inject openpix react - giftback plugin
        add_action('wp_enqueue_scripts', [$this, 'checkout_scripts']);

        add_action('woocommerce_after_order_details', [
            $this,
            'afterOrderDetailHook',
        ]);

        // $this->registerHooks();
    }

    // giftback
    public function checkout_scripts()
    {
        if (is_checkout()) {
            $name = get_bloginfo('name');

            $correlationID = $this->getCorrelationID();

            WC_OpenPix::debug('get correlationID result ' . $correlationID);

            wp_localize_script('openpix-checkout', 'wcOpenpixParams', [
                'appID' => $this->appID,
                'storeName' => $name,
                'correlationID' => $correlationID,
            ]);
        }
    }

    function validSignature($payload, $signature)
    {
        $publicKey = base64_decode(OpenPixConfig::$OPENPIX_PUBLIC_KEY_BASE64);

        $verify = openssl_verify(
            $payload,
            base64_decode($signature),
            $publicKey,
            'sha256WithRSAEncryption'
        );

        return $verify === 1 ? true : false;
    }

    public function getCorrelationID()
    {
        $correlationIDFromSession = WC()->session->get('correlationID');

        WC_OpenPix::debug(
            'correlationIDFromSession ' . $correlationIDFromSession
        );

        if (isset($correlationIDFromSession)) {
            return $correlationIDFromSession;
        }

        $correlationID = WC_OpenPix::uuid_v4();

        WC()->session->set('correlationID', $correlationID);

        return $correlationID;
    }

    /**
     * Check if the provided data is a valid test webhook payload.
     *
     * @param array $data The data to be validated.
     *
     * @return bool Returns true if the data contains the required 'evento' field, otherwise returns false.
     */
    public function isValidTestWebhookPayload($data)
    {
        if (isset($data['evento'])) {
            return true;
        }

        return false;
    }

    /**
     * Check if the provided data is a valid webhook payload.
     *
     * @param array $data The data to be validated.
     *
     * @return bool Returns true if the data contains any of the required keys (charge, pix, or event), otherwise returns false.
     */
    public function isValidWebhookPayload($data)
    {
        if(!isset($data['event']) || empty($data['event'])) {
            return false;
        }

        // @todo remove it and update evento to event
        if(!isset($data['evento']) || empty($data['evento'])) {
            return false;
        }

        return true;
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

    /**
     * Check if the provided data is a valid webhook payload.
     *
     * @param array $data The data to be validated.
     *
     * @return bool Returns true if the data contains any of the required keys (charge, pix, or event), otherwise returns false.
     */
    public function handleIntegrationConfiguration($data): bool
    {
        $hasAppID = isset($data['appID']);
        $alreadyHasAppID = $this->get_option('appID');
            
        if($alreadyHasAppID) {
            header('HTTP/1.1 400 Bad Request');
            $response = [
                'message' => __('App ID already configured', 'openpix'),
            ];
            echo json_encode($response);
            exit();
        }

        if(!$hasAppID) {
            header('HTTP/1.1 400 Bad Request');
            $response = [
                'message' => __('App ID is required', 'openpix'),
            ];
            echo json_encode($response);
            exit();
        }

        $this->configureIntegration($data);

        header('HTTP/1.1 200 OK');
        $response = [
            'message' => 'success',
        ];
        echo json_encode($response);
        exit();
    }

    public function configureIntegration($data)
    {
        $this->update_option('appID', $data['appID']);
    }

    public function handleTestWebhook($data)
    {
        WC_OpenPix::debug('handleTestWebhook');
        header('HTTP/1.1 200 OK');
        $response = [
            'message' => 'success',
        ];
        echo json_encode($response);
        exit();
    }

    public function handleWebhookOrderUpdate($data) {
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

    public function handleWebhookEvents($data, $body) {
        $event = $data['event'];
        // @todo: refactor this to follow event instead evento
        $evento = $data['evento'];

        if($evento === 'teste_webhook') {
            $this->handleTestWebhook($data);
        }

        if($event === 'woocommerce-configure') {
            $this->handleIntegrationConfiguration($data);
            return;
        }

        if($event === 'OPENPIX:TRANSACTION_RECEIVED' || $event === 'OPENPIX:CHARGE_COMPLETED') {
            $this->handleWebhookOrderUpdate($data);
            return;
        }
    }

    public function validateWebhook($data, $body)
    {
        $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? null;

        if (!$signature || !$this->validSignature($body, $signature)) {
            header('HTTP/1.2 400 Bad Request');
            $response = [
                'error' => 'Invalid Webhook signature',
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

        if ($this->isPixDetachedPayload($data)) {
            header('HTTP/1.1 200 OK');

            $response = [
                'message' => 'Pix Detached',
            ];
            echo json_encode($response);
            exit();
        }
    }
    /**
     * Handles incoming IPN (Instant Payment Notification) requests.
     * 
     * This is the main entry point for the IPN requests.
     *
     * @return void
     */
    public function ipn_handler()
    {
        global $wpdb;
        @ob_clean();
        $body = file_get_contents('php://input', true);
        $data = json_decode($body, true);

        $this->validateWebhook($data, $body);

        $this->handleWebhookEvents($data, $body);
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
        
        $webhookUrl = OpenPixConfig::getWebhookUrl();

        $webhookLabel = sprintf(
            __(
                'Use this Webhook URL to be registered at OpenPix: %s',
                'woocommerce-openpix'
            ),
            '<a target="_blank" href="' .
                $webhookUrl .
                '">' .
                $webhookUrl .
                '</a>'
        );

        $registerLabel = sprintf(
            __(
                'Open your account now %s',
                'woocommerce-openpix'
            ),
            '<a target="_blank" href="https://app.openpix.com/register">https://app.openpix.com/register</a>'
        );

        $documentationLabel = sprintf(
            __(
                'OpenPix integration %s with Woocommerce',
                'woocommerce-openpix'
            ),
            '<a target="_blank" href="https://developers.openpix.com.br/docs/ecommerce/woocommerce/woocommerce-plugin#instale-o-plugin-openpix-na-sua-inst%C3%A2ncia-woocommerce-utilizando-one-click">documentation</a>'
        );
        
        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'woocommerce-openpix'),
                'type' => 'checkbox',
                'label' => __('Enable OpenPix', 'woocommerce-openpix'),
                'default' => 'no',
                'description' => "<p>$webhookLabel</p><p>$registerLabel</p><p>$documentationLabel</p>"
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
            ],
            'webhook_button' => [
                'type' => 'button',
                'title' => __('One Click Configuration', 'woocommerce-openpix'),
                'class' => 'button-primary',
                'description' => sprintf(
                    __(
                        'Follow documentation to configure Webhook on OpenPix here %s.',
                        'woocommerce-openpix'
                    ),
                    '<a target="_blank" href="https://developers.openpix.com.br/docs/ecommerce/woocommerce-plugin/">' .
                        __(
                            'Documentation',
                            'woocommerce-openpix'
                        ) .
                        '</a>'
                ),
            ],
            'webhook_status' => [
                'type' => 'text',
                'title' => __('Webhook Status', 'woocommerce-openpix'),
                'description' => __('Status ', 'woocommerce-openpix'),
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
        if ($description = $this->get_description()) {
            echo wp_kses_post(wpautop(wptexturize($description)));
        }

        $cart_total = $this->get_order_total();

        echo '<div id="openpix-checkout-params" ';
        echo 'data-total="' . esc_attr($cart_total * 100) . '" ';
        echo '></div>';
    }

    public function get_openpix_amount($total)
    {
        return absint(
            wc_format_decimal((float) $total * 100, wc_get_price_decimals())
        ); // In cents.
    }

    public function get_wc_amount($total)
    {
        return absint($total / 100); // money.
    }

    public function formatPhone($phone)
    {
        $phoneSafe = preg_replace('/^0|\D+/', '', $phone);
        if (strlen($phoneSafe) > 11) {
            return $phoneSafe;
        }

        return '55' . $phoneSafe;
    }

    public function getHasCustomer($order)
    {
        $hasOpenpixCustomer = isset($_POST['openpix_customer_taxid']);

        if ($hasOpenpixCustomer) {
            return true;
        }

        $order_billing_cpf = $order->get_meta('_billing_cpf');
        $order_billing_cnpj = $order->get_meta('_billing_cnpj');

        return isset($order_billing_cpf) || isset($order_billing_cnpj);
    }

    // @ TODO: why should prioritize the logged shopper?
    public function getTaxID($order)
    {
        $openpix_customer_taxid = $_POST['openpix_customer_taxid'];

        $hasOpenpixCustomer =
            isset($openpix_customer_taxid) && !empty($openpix_customer_taxid);

        if ($hasOpenpixCustomer) {
            return sanitize_text_field($openpix_customer_taxid);
        }

        $order_persontype = $order->get_meta('_billing_persontype');
        $order_billing_cpf = $order->get_meta('_billing_cpf');
        $order_billing_cnpj = $order->get_meta('_billing_cnpj');

        if (!empty($order_persontype)) {
            if ($order_persontype === '1') {
                return !empty($order_billing_cpf)
                    ? sanitize_text_field($order_billing_cpf)
                    : sanitize_text_field($order_billing_cnpj);
            }

            return !empty($order_billing_cnpj)
                ? sanitize_text_field($order_billing_cnpj)
                : sanitize_text_field($order_billing_cpf);
        }

        return !empty($order_billing_cpf)
            ? sanitize_text_field($order_billing_cpf)
            : sanitize_text_field($order_billing_cnpj);
    }

    public function getCustomerData($order)
    {
        $order_billing_cpf = $order->get_meta('_billing_cpf');
        $order_billing_cnpj = $order->get_meta('_billing_cnpj');

        $hasCustomer = $this->getHasCustomer($order);

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

        $phone =
            isset($order_billing_cellphone) && !empty($order_billing_cellphone)
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

    public function getGiftbackData()
    {
        $hasGiftback =
            isset($_POST['openpix_giftback_hash']) &&
            isset($_POST['openpix_giftback_value']) &&
            isset($_POST['openpix_shopper_id']);

        if (!$hasGiftback) {
            WC_OpenPix::debug('Not has giftback data');
            return null;
        }

        $order_giftback_hash = $_POST['openpix_giftback_hash'];
        $order_giftback_value = intval($_POST['openpix_giftback_value']);
        $order_shopper_id = $_POST['openpix_shopper_id'];

        $giftback = [
            'giftbackHash' => $order_giftback_hash,
            'giftbackValue' => $order_giftback_value,
            'shopperId' => $order_shopper_id,
        ];

        WC_OpenPix::debugJson('Giftback data', $giftback);

        return $giftback;
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

    public function shouldApplyGiftback($data)
    {
        WC_OpenPix::debugJson('Giftback data', $data);
        if (
            isset($data['charge']['giftbackAppliedValue']) &&
            !empty($data['charge']['giftbackAppliedValue']) &&
            $data['charge']['giftbackAppliedValue'] > 0
        ) {
            return true;
        }
        return false;
    }

    public function getErrorFromResponse($response)
    {
        $body = json_decode($response['body'], true);

        if (isset($body['error'])) {
            return $body['error'];
        }

        return '';
    }

    public function process_payment($order_id)
    {
        global $woocommerce;
        $order = wc_get_order($order_id);

        $correlationID = WC_OpenPix::uuid_v4();

        $url = OpenPixConfig::getApiUrl() . '/api/v1/charge';

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
        WC_OpenPix::debugJson('Charge post payload:', $payload);

        if (OpenPixConfig::getEnv() === 'development') {
            $response = wp_remote_post($url, $params);
        } else {
            $response = wp_safe_remote_post($url, $params);
        }

        if (is_wp_error($response)) {
            wc_add_notice(
                __('Error creating Pix, try again', 'woocommerce-openpix'),
                'error'
            );

            $error_message = $response->get_error_message();

            WC_OpenPix::debug('Error creating pix: ' . $error_message);

            return [
                'result' => 'fail',
            ];
        }

        WC_OpenPix::debugJson('Charge post response:', $response['body']);

        if ($response['response']['code'] === 401) {
            wc_add_notice(__('Invalid AppID', 'woocommerce-openpix'), 'error');

            WC_OpenPix::debugJson('Error creating pix:', $response);

            return [
                'result' => 'fail',
            ];
        }

        if ($response['response']['code'] !== 200) {
            WC_OpenPix::debugJson('Error creating pix:', $response);

            $errorMessage = $this->getErrorFromResponse($response);

            wc_add_notice(
                __('Error creating Pix, try again', 'woocommerce-openpix'),
                'error'
            );

            if (isset($errorMessage) && !empty($errorMessage)) {
                wc_add_notice($errorMessage, 'error');
            }

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

        if ($this->shouldApplyGiftback($data)) {
            $giftbackAppliedValueOnCharge =
                $data['charge']['giftbackAppliedValue'];
            $nonNegativeGiftback = absint($giftbackAppliedValueOnCharge);
            $roundedGiftbackValue = round($nonNegativeGiftback / 100, 2);

            $coupon = new AWPCustomDiscount(
                'giftback-' . $order_id,
                $roundedGiftbackValue,
                'fixed_cart'
            );

            $coupon->addDiscount($order);
        }

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

        $order->add_order_note(
            __(
                "OpenPix: Payment link: <a href='{$data['charge']['paymentLinkUrl']}'>{$data['charge']['paymentLinkUrl']}</a>",
                'woocommerce-openpix'
            )
        );

        WC()->cart->empty_cart();

        return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }
    public static function openpix_configure_webhook()
    {
        $webhookUrl = OpenPixConfig::getWebhookUrl();

        $url = OpenPixConfig::getApiUrl() . '/api/v1/webhook';
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

        $payload = [
            'webhook' => [
                'name' => 'WooCommerce-Webhook',
                'url' => $webhookUrl,
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

        // @todo: put signature here

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
        $data = $this->getPluginSrc($order_id);

        wc_get_template(
            'payment-instructions.php',
            [
                'paymentLinkUrl' => $data['orderData']['paymentLinkUrl'],
                'qrCodeImage' => $data['orderData']['qrCodeImage'],
                'brCode' => $data['orderData']['brCode'],
                'correlationID' => $data['correlationID'],
                'environment' => $data['environment'],
                'appID' => $this->appID,
                'pluginUrl' => WC_OpenPix::get_assets_url(),
                'src' => $data['src'],
            ],
            WC_OpenPix::get_templates_path(),
            WC_OpenPix::get_templates_path()
        );
    }

    public function getPluginSrc($order_id)
    {
        $data = get_post_meta($order_id, 'openpix_transaction', true);
        $correlationID = get_post_meta(
            $order_id,
            'openpix_correlation_id',
            true
        );

        $environment = OpenPixConfig::getEnv();
        $queryString = "appID={$this->appID}&correlationID={$correlationID}&node=openpix-order";
        $pluginUrl = OpenPixConfig::getPluginUrl();
        return [
            'orderData' => $data,
            'correlationID' => $correlationID,
            'environment' => $environment,
            'queryString' => $queryString,
            'pluginUrl' => $pluginUrl,
            'src' => "$pluginUrl?$queryString",
        ];
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

    // giftback growth
    public function ceHandlerWooCommerceNewOrder($order)
    {
        try {
            if (is_numeric($order)) {
                $order = wc_get_order($order);
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
                'body' => json_encode(['raw' => json_decode($order, true)]),
                'method' => 'POST',
                'data_format' => 'body',
            ];

            wp_remote_post($this->getWebhookTrack(), $params);
        } catch (\Exception $exception) {
        }
    }

    public function registerHooks()
    {
        $actions = [
            'woocommerce_reduce_order_stock',
            'woocommerce_payment_complete',
            'woocommerce_order_status_completed',
            'woocommerce_checkout_order_created',
        ];
        foreach ($actions as $action) {
            add_action($action, [$this, 'ceHandlerWooCommerceNewOrder']);
        }
    }

    public function afterOrderDetailHook($order)
    {
        $page = get_post();
        if ($page->post_name != 'my-account' || $page->ID != 9) {
            return;
        }

        $data = $this->getPluginSrc($order->get_id());
        ?>
        <div id="openpix-order"></div>
        <script src="<?= $data['src'] ?>" async></script>
        <?php
    }
}
