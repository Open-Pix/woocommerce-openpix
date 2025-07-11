<?php

if (!defined('ABSPATH')) {
    exit();
}

require_once plugin_dir_path(__FILE__) . 'config/autoload.php';

add_action('admin_footer', 'embedWebhookConfigButton');

function embedWebhookConfigButton()
{
    ?>

	<script type="text/javascript" >
	jQuery(document).ready(function($) {

        jQuery("#woocommerce_woocommerce_openpix_pix_oneclick_button").click(() => {
            var data = {
                action: 'openpix_prepare_oneclick',
            };

            jQuery.post(ajaxurl,data,function(response) {
                var redirect_url = response.redirect_url || "";

                if (redirect_url) {
                    window.open(redirect_url, "_blank");
                }
            })
        })
	});
	</script> <?php
}
add_action('wp_ajax_openpix_prepare_oneclick', [
    'WC_OpenPix_Pix_Gateway',
    'openpix_prepare_oneclick',
]);
function wc_openpix_assets_url()
{
    return plugin_dir_url(dirname(__FILE__)) . 'assets/';
}

require_once __DIR__ . '/customer/class-wc-openpix-customer.php';

class WC_OpenPix_Pix_Gateway extends WC_Payment_Gateway
{
    public $appID;
    public $status_when_waiting;
    public $status_when_paid;
    private $redirect_url_after_paid;
    private $openpix_customer;
    private $environment;

    private static $instance = null;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        $this->openpix_customer = new WC_OpenPix_Customer();

        $this->id = 'woocommerce_openpix_pix';
        $this->method_title = __('OpenPix Pix', 'woocommerce-openpix');
        $this->method_description = __(
            'Accept Pix payments with real-time updates, seamless checkout, and automatic order status updates.',
            'woocommerce-openpix'
        );
        $this->has_fields = true; // direct payment
        $this->supports = ['products', 'refunds'];

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        $this->order_button_text = $this->get_option('order_button_text');
        $this->appID = $this->get_option('appID');
        $this->environment = $this->get_option('environment') ?? EnvironmentEnum::PRODUCTION;

        OpenPixConfig::initialize($this->environment);

        $this->status_when_waiting = $this->get_option('status_when_waiting');
        $this->status_when_paid = $this->get_option('status_when_paid');

        $this->redirect_url_after_paid = $this->get_option(
            'redirect_url_after_paid'
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

        add_action('woocommerce_after_order_details', [
            $this,
            'afterOrderDetailHook',
        ]);

        add_action('woocommerce_openpix_pix_email', [$this, 'displayEmail']);
    }

    public function displayEmail($order)
    {
        if ($order->get_payment_method() !== $this->id) {
            return;
        }

        $orderData = $order->get_meta('openpix_transaction');

        if (!$orderData || !is_array($orderData)) {
            return;
        }

        $qrCodeImage = $orderData['qrCodeImage'];
        $brCode = $orderData['brCode'];
        ?>

        <strong>Pague com Pix QR Code:</strong>

        <br />

        <img src="<?= $qrCodeImage ?>" width="256" />

        <br />

        <strong>Pague com Pix Copia e Cola:</strong>

        <br />

        <p style="overflow-wrap:anywhere">
            <?= $brCode ?>
        </p>

        <?php
    }

    public function can_refund_order($order)
    {
        return true;
    }

    public function process_refund($order_id, $amount = null, $reason = null)
    {
        $order = wc_get_order($order_id);

        $chargeCorrelationID = $order->get_meta('openpix_correlation_id');

        $url =
            OpenPixConfig::getApiUrl() .
            "/api/v1/charge/$chargeCorrelationID/refund";

        $total_cents = $this->get_openpix_amount($amount);

        $payload = [
            'correlationID' => WC_OpenPix::uuid_v4(),
            'value' => $total_cents,
            'comment' => $reason,
        ];

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

        WC_OpenPix::debugJson('Charge refund payload:', $payload);

        if (OpenPixConfig::getEnv() === 'development') {
            $response = wp_remote_post($url, $params);
        } else {
            $response = wp_safe_remote_post($url, $params);
        }

        if (is_wp_error($response)) {
            wc_add_notice(
                __('Error refunding charge', 'woocommerce-openpix'),
                'error'
            );

            $error_message = $response->get_error_message();

            WC_OpenPix::debug('Error refunding charge: ' . $error_message);

            return false;
        }

        WC_OpenPix::debugJson('Charge refund response:', $response['body']);

        $code = $response['response']['code'];

        if ($code === 400) {
            wc_add_notice(__('Invalid AppID', 'woocommerce-openpix'), 'error');

            WC_OpenPix::debugJson("Error refunding charge $code:", $response);

            $body = json_decode($response['body']);

            if ($body && $body->error) {
                return new WP_Error($body->error, $body->error);
            }

            return false;
        }

        if ($code !== 200) {
            WC_OpenPix::debugJson("Error refunding charge $code:", $response);

            $errorMessage = $this->getErrorFromResponse($response);

            wc_add_notice(
                __('Error refunding charge', 'woocommerce-openpix'),
                'error'
            );

            if (isset($errorMessage) && !empty($errorMessage)) {
                wc_add_notice($errorMessage, 'error');
            }

            return false;
        }

        return true;
    }

    public function process_admin_options()
    {
        $old_environment = $this->get_option('environment');
        $saved = parent::process_admin_options();

        WC_OpenPix::debug('process_admin_options');
        if ($saved) {
            $new_environment = $this->get_option('environment');
            
            // Always update the environment option to ensure consistency
            if ($old_environment !== $new_environment) {
                WC_OpenPix::debug(
                    'Environment changed from ' . $old_environment . ' to ' . $new_environment
                );
                $this->update_option('environment', $new_environment);
                OpenPixConfig::initialize($new_environment);
            }
        }
        
        return $saved;
    }

    function validSignature($payload, $signature)
    {
        $publicKey = base64_decode(OpenPixConfig::getPublicKeyBase64());

        $verify = openssl_verify(
            $payload,
            base64_decode($signature),
            $publicKey,
            'sha256WithRSAEncryption'
        );

        return $verify === 1 ? true : false;
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
        if (!isset($data['event']) || empty($data['event'])) {
            if (!isset($data['evento']) || empty($data['evento'])) {
                return false;
            }
        }

        // @todo remove it and update evento to event

        return true;
    }

    public function isHposEnabled()
    {
        if (
            !method_exists(
                \Automattic\WooCommerce\Utilities\OrderUtil::class,
                'custom_orders_table_usage_is_enabled'
            )
        ) {
            return false;
        }

        return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    }

    public function get_order_by_correlation_id_legacy($correlation_id)
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
            return wc_get_order($order_id);
        }

        return false;
    }

    public function get_order_by_correlation_id_hpos($correlation_id)
    {
        if (empty($correlation_id)) {
            return false;
        }

        $orders = wc_get_orders([
            'meta_query' => [
                [
                    'key' => 'openpix_correlation_id',
                    'value' => $correlation_id,
                ],
            ],
        ]);

        if (!empty($orders[0])) {
            return $orders[0];
        }

        return null;
    }

    public function get_order_by_correlation_id($correlation_id)
    {
        if (!$this->isHposEnabled()) {
            return $this->get_order_by_correlation_id_legacy($correlation_id);
        }

        return $this->get_order_by_correlation_id_hpos($correlation_id);
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

        if ($alreadyHasAppID) {
            header('HTTP/1.1 400 Bad Request');
            $response = [
                'message' => __('App ID already configured', 'openpix'),
            ];
            echo json_encode($response);
            exit();
        }

        if (!$hasAppID) {
            header('HTTP/1.1 400 Bad Request');
            $response = [
                'message' => __('App ID is required', 'openpix'),
            ];
            $this->update_option('webhook_status', 'Not Configured');
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
        $this->update_option('webhook_status', 'Configured');
        
        // Update environment if provided in the webhook data
        // I don't know if it makes sense to keep this
        // @todo: remove this after testing
        // if (isset($data['environment'])) {
        //     $this->update_option('environment', $data['environment']);
        //     OpenPixConfig::initialize($data['environment']);
        // }
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

    public function get_order_meta($order, $name, $single = true)
    {
        if (!$this->isHposEnabled()) {
            return get_post_meta($order->get_id(), $name, $single);
        }

        return $order->get_meta($name);
    }

    public function handleWebhookOrderUpdate($data)
    {
        $correlationID = $data['charge']['correlationID'];
        $status = $data['charge']['status'];
        $endToEndId = $data['pix']['endToEndId'];

        $order = $this->get_order_by_correlation_id($correlationID);

        if (!$order) {
            WC_OpenPix::debug(
                'Cound not find order with correlation ID ' . $correlationID
            );
            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' => 'order not found',
                'order_id' => null,
                'correlationId' => $correlationID,
                'status' => $status,
            ];
            echo json_encode($response);
            exit();
        }

        $order_correlation_id = $this->get_order_meta(
            $order,
            'openpix_correlation_id',
            true
        );
        $order_end_to_end_id = $this->get_order_meta(
            $order,
            'openpix_endToEndId',
            true
        );

        if ($order_end_to_end_id) {
            WC_OpenPix::debug('Order already paid ' . $order->get_id());

            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' => 'order already with end to end id',
                'order_id' => $order->get_id(),
                'correlationId' => $correlationID,
                'status' => $status,
            ];

            echo json_encode($response);
            exit();
        }

        if (!$order_correlation_id) {
            WC_OpenPix::debug(
                'Order without correlation id ' . $order->get_id()
            );

            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' => 'order without correlation id',
                'order_id' => $order->get_id(),
                'correlationId' => $correlationID,
                'status' => $status,
            ];

            echo json_encode($response);
            exit();
        }

        if ($order_correlation_id !== $correlationID) {
            WC_OpenPix::debug(
                'Order with different correlation id then webhook correlation id ' .
                    $order->get_id()
            );

            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' =>
                    'order with different correlation id ' .
                    $order_correlation_id,
                'order_id' => $order->get_id(),
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

                    foreach ($meta_data as $key => $value) {
                        $order->update_meta_data($key, $value);
                    }

                    $order->save();
                }
            }
        }

        header('HTTP/1.1 200 OK');

        $response = [
            'message' => 'success',
            'order_id' => $order->get_id(),
            'correlationId' => $correlationID,
            'status' => $status,
        ];

        echo json_encode($response);
        exit();
    }

    public function handleWebhookEvents($data, $body)
    {
        $event = $data['evento'] ?? $data['event'];
        // @todo: refactor this to follow event instead evento

        if ($event === 'teste_webhook') {
            $this->handleTestWebhook($data);
        }

        if ($event === 'woocommerce-configure') {
            $this->handleIntegrationConfiguration($data);
            return;
        }

        if (
            $event === 'OPENPIX:TRANSACTION_RECEIVED' ||
            $event === 'OPENPIX:CHARGE_COMPLETED'
        ) {
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
            __('Open your account now %s', 'woocommerce-openpix'),
            '<a target="_blank" href="https://app.woovi.com/register">https://app.woovi.com/register</a>'
        );

        $documentationLabel = sprintf(
            __(
                'OpenPix integration %s with Woocommerce',
                'woocommerce-openpix'
            ),
            '<a target="_blank" href="https://developers.woovi.com/docs/ecommerce/woocommerce/woocommerce-plugin#instale-o-plugin-openpix-na-sua-inst%C3%A2ncia-woocommerce-utilizando-one-click">documentation</a>'
        );

        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'woocommerce-openpix'),
                'type' => 'checkbox',
                'label' => __('Enable OpenPix', 'woocommerce-openpix'),
                'default' => 'no',
                'description' => "<p>$webhookLabel</p><p>$registerLabel</p><p>$documentationLabel</p>",
            ],
            'api_section' => [
                'title' => __('OpenPix Integration API', 'woocommerce-openpix'),
                'type' => 'title',
                'description' => sprintf(
                    __(
                        'Follow documentation to get your OpenPix AppID here %s.',
                        'woocommerce-openpix'
                    ),
                    '<a target="_blank" href="https://developers.woovi.com/docs/apis/api-getting-started/">' .
                        __(
                            'OpenPix API Getting Started',
                            'woocommerce-openpix'
                        ) .
                        '</a>'
                ),
            ],
            'environment' => [
                'title' => __('Ambiente', 'woocommerce-openpix'),
                'type' => 'select',
                'description' => __('Selecione o ambiente de integração', 'woocommerce-openpix'),
                'default' => 'prod',
                'options' => [
                    'sandbox-prod' => 'Sandbox',
                    'prod' => 'Production'
                ],
            ],
            'oneclick_section' => [
                'title' => __(
                    'Authenticate on the platform using 1 click',
                    'woocommerce-openpix'
                ),
                'type' => 'title',
            ],
            'oneclick_button' => [
                'type' => 'button',
                'title' => __('One Click Configuration', 'woocommerce-openpix'),
                'class' => 'button-primary',
                'description' => sprintf(
                    __(
                        'By pressing this button, you will be redirected to our platform where we will quickly configure a new integration.',
                        'woocommerce-openpix'
                    )
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
            'actions_section' => [
                'title' => __('Configure actions', 'woocommerce-openpix'),
                'type' => 'title',
            ],
            'redirect_url_after_paid' => [
                'type' => 'text',
                'title' => __(
                    'Redirect URL after confirmation of payment',
                    'woocommerce-openpix'
                ),
                'description' => __('Redirect URL ', 'woocommerce-openpix'),
            ],
        ];

        if (!$this->get_option('oneclick_button')) {
            $this->update_option(
                'oneclick_button',
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
        if (!class_exists('libphonenumber\PhoneNumberUtil')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }

        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        
        // Remove all non-numeric characters
        $cleanNumber = preg_replace('/\D+/', '', $phone);
        
        // Try first validation with original number
        try {
            $numberProto = $phoneUtil->parse($cleanNumber, 'BR');
            if ($phoneUtil->isValidNumber($numberProto)) {
                $formattedNumber = $phoneUtil->format($numberProto, \libphonenumber\PhoneNumberFormat::E164);
                return ltrim($formattedNumber, '+');
            }
        } catch (\libphonenumber\NumberParseException $e) {
            // Continue to try alternative format if this fails
        }
        
        // If the first validation fails and number starts with 5555,
        // try removing one instance of 55 and validate again
        if (strpos($cleanNumber, '5555') === 0) {
            $alternativeNumber = '55' . substr($cleanNumber, 4);
            
            try {
                $numberProto = $phoneUtil->parse($alternativeNumber, 'BR');
                if ($phoneUtil->isValidNumber($numberProto)) {
                    $formattedNumber = $phoneUtil->format($numberProto, \libphonenumber\PhoneNumberFormat::E164);
                    return ltrim($formattedNumber, '+');
                }
            } catch (\libphonenumber\NumberParseException $e) {
                // If both attempts fail, return false
                return false;
            }
        }
        
        return false;
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
        $hasOpenpixCustomer =
            isset($_POST['openpix_customer_taxid']) &&
            !empty($_POST['openpix_customer_taxid']);

        if ($hasOpenpixCustomer) {
            return sanitize_text_field($_POST['openpix_customer_taxid']);
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

        $formattedPhone = $this->formatPhone($phone);

        $customer = [
            'name' => $name,
            'email' => $email,
            'taxID' => $taxID,
        ];

        // Only add phone if formatPhone returned a valid value
        if ($formattedPhone !== false) {
            $customer['phone'] = $formattedPhone;
        }

        $address = $this->openpix_customer->getCustomerAddress($order);

        if ($this->canSendCustomerAddress($address)) {
            $customer['address'] = $address;
        }

        return $customer;
    }

    private function canSendCustomerAddress($address)
    {
        if (!is_array($address)) {
            return false;
        }

        $hasMissingFields =
            empty($address['zipcode']) ||
            empty($address['street']) ||
            empty($address['number']) ||
            empty($address['neighborhood']) ||
            empty($address['city']) ||
            empty($address['state']) ||
            empty($address['country']);

        if ($hasMissingFields) {
            return false;
        }

        return true;
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

    public function getErrorFromResponse($response)
    {
        $body = json_decode($response['body'], true);

        if (isset($body['error'])) {
            return $body['error'];
        }

        return '';
    }

    public function generate_correlation_id($order)
    {
        $order_key = $order->get_order_key();
        $order_id = $order->get_id();

        return $order_key . '-' . $order_id;
    }

    public function process_payment($order_id)
    {
        global $woocommerce;
        $order = wc_get_order($order_id);

        $correlationID = $this->generate_correlation_id($order);
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

        // update the correlationID before sending the request to ensure it's the same one used in the response
        $order->update_meta_data('openpix_correlation_id', $correlationID);

        $order->save();

        $url = add_query_arg('return_existing', 'true', $url);
        WC_OpenPix::debugJson('Charge post URL:', $url);

        $params = [
            'timeout' => 120,
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

        foreach ($meta_data as $key => $value) {
            $order->update_meta_data($key, $value);
        }

        $order->save();

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

    public static function openpix_prepare_oneclick()
    {
        $webhookUrl = OpenPixConfig::getWebhookUrl();
        $platformUrl = OpenPixConfig::getPlatformUrl();
        $platformNewIntegrationUrl =
            $platformUrl .
            '/home/applications/woocommerce/add/oneclick?website=' .
            $webhookUrl;

        // Remove current App ID and reset environment
        $openpixSettings = get_option(
            'woocommerce_woocommerce_openpix_pix_settings'
        );

        if (!is_array($openpixSettings)) {
            $openpixSettings = [];
        }

        $openpixSettings['appID'] = '';
        $openpixSettings['environment'] = 'prod'; // Reset to production by default
        $openpixSettings['webhook_status'] = 'Not Configured';

        update_option(
            'woocommerce_woocommerce_openpix_pix_settings',
            $openpixSettings
        );

        $response = [
            'redirect_url' => $platformNewIntegrationUrl,
        ];

        wp_send_json($response);
        wp_die();
    }

    public function thankyou_page($order_id)
    {
        $data = $this->getPluginSrc($order_id);

        WC_OpenPix::debugJson('thankyou data', $data);

        wc_get_template(
            'payment-instructions.php',
            [
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
        $order = wc_get_order($order_id);

        $data = $this->get_order_meta($order, 'openpix_transaction', true);
        $correlationID = $this->get_order_meta(
            $order,
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

        $checkoutKey =
            !empty($order) && method_exists($order, 'get_order_key')
                ? $order->get_order_key()
                : '';

        $variables = [
            'correlationID' => $correlationID,
            'order_id' => $order_id,
            'key' => $checkoutKey,
        ];

        $redirectUrl = $this->makeRedirectUrlAfterPaid($variables);

        if (!empty($redirectUrl)) {
            $payload['redirectUrl'] = $redirectUrl;
        }

        return $payload;
    }

    private function makeRedirectUrlAfterPaid($variables)
    {
        $redirectUrl = html_entity_decode($this->redirect_url_after_paid);

        if (empty($redirectUrl)) {
            return '';
        }

        $queryParams = parse_url($redirectUrl, PHP_URL_QUERY);
        $mergedQueryParams =
            ($queryParams ? '&' : '?') . http_build_query($variables);

        $finalUrl = $redirectUrl . $mergedQueryParams;

        if (!empty($variables['order_id'])) {
            $finalUrl = str_replace(
                ':orderId',
                $variables['order_id'],
                $finalUrl
            );
        }

        return $finalUrl;
    }

    public function afterOrderDetailHook($order)
    {
        $canShowQrCode =
            is_account_page() && $order->get_payment_method() == $this->id;

        if (!$canShowQrCode) {
            return;
        }

        $data = $this->getPluginSrc($order->get_id());
        ?>
        <div id="openpix-order"></div>
        <script src="<?= $data['src'] ?>" async></script>
        <?php
    }

    public function getAppID(): string
    {
        return $this->appID;
    }
}
