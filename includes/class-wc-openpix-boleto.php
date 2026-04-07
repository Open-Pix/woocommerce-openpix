<?php

if (!defined('ABSPATH')) {
    exit();
}
require_once plugin_dir_path(__FILE__) . 'config/autoload.php';

if (!function_exists('embedBoletoWebhookConfigButton')) {
    function embedBoletoWebhookConfigButton()
    {
        $nonce = wp_create_nonce('openpix_prepare_oneclick_nonce'); ?>

        <script type="text/javascript" >
        jQuery(document).ready(function($) {

            jQuery("#woocommerce_woocommerce_openpix_boleto_oneclick_button").click(() => {
                var data = {
                    action: 'openpix_prepare_oneclick',
                    nonce: '<?php echo esc_js($nonce); ?>'
                };

                jQuery.post(ajaxurl,data,function(response) {
                    if (response.success === false) {
                        alert(response.data || 'Error: Unauthorized request');
                        return;
                    }
                    var redirect_url = response.redirect_url || "";

                    if (redirect_url) {
                        window.open(redirect_url, "_blank");
                    }
                })
            })
        });
        </script> <?php
    }
}
add_action('admin_footer', 'embedBoletoWebhookConfigButton');

class WC_OpenPix_Boleto_Gateway extends WC_Payment_Gateway
{
    public $appID;
    public $status_when_waiting;
    public $status_when_paid;
    private $redirect_url_after_paid;
    private $openpix_customer;
    private $environment;
    private $config;

    private static $instance = null;
    private static $hooks_registered = false;

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

        $this->id = 'woocommerce_openpix_boleto';
        $this->method_title = __('OpenPix Boleto', 'openpix-for-woocommerce');
        $this->method_description = __(
            'Accept Boleto payments with real-time updates.',
            'openpix-for-woocommerce'
        );
        $this->has_fields = true;
        $this->supports = ['products', 'refunds'];

        // Initialize config from WP options before init_form_fields (which needs config)
        $raw_settings = get_option(
            'woocommerce_' . $this->id . '_settings',
            []
        );
        $this->environment = isset($raw_settings['environment'])
            ? $raw_settings['environment']
            : EnvironmentEnum::PRODUCTION;
        $this->config = ConfigFactory::createStrategy($this->environment);

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        $this->order_button_text = $this->get_option('order_button_text');
        $this->appID = $this->get_option('appID');

        $this->status_when_waiting = $this->get_option('status_when_waiting');
        $this->status_when_paid = $this->get_option('status_when_paid');

        $this->redirect_url_after_paid = $this->get_option(
            'redirect_url_after_paid'
        );

        if (!self::$hooks_registered) {
            self::$hooks_registered = true;

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [
                $this,
                'process_admin_options',
            ]);

            add_action('woocommerce_thankyou_' . $this->id, [
                $this,
                'thankyou_page',
            ]);

            add_action('woocommerce_receipt_' . $this->id, [
                $this,
                'thankyou_page',
            ]);

            add_action('woocommerce_view_order', [$this, 'thankyou_page']);

            add_action('woocommerce_after_order_details', [
                $this,
                'afterOrderDetailHook',
            ]);

            add_action('woocommerce_api_wc_openpix_boleto_gateway', [
                $this,
                'ipn_handler',
            ]);
        }
    }

    public function ipn_handler()
    {
        $this->webhook();
    }

    public function init_form_fields()
    {
        $webhookUrl = $this->config->getWebhookUrl('WC_OpenPix_Boleto_Gateway');

        $webhookLabel = sprintf(
            /* translators: %s: webhook URL link */
            __(
                'Use this Webhook URL to be registered at OpenPix: %s',
                'openpix-for-woocommerce'
            ),
            '<a target="_blank" href="' .
                $webhookUrl .
                '">' .
                $webhookUrl .
                '</a>'
        );

        $registerLabel = sprintf(
            /* translators: %s: registration link */
            __('Open your account now %s', 'openpix-for-woocommerce'),
            '<a target="_blank" href="https://app.woovi.com/register">https://app.woovi.com/register</a>'
        );

        $documentationLabel = sprintf(
            /* translators: %s: documentation link */
            __(
                'OpenPix integration %s with Woocommerce',
                'openpix-for-woocommerce'
            ),
            '<a target="_blank" href="https://developers.woovi.com/docs/ecommerce/woocommerce/woocommerce-plugin#instale-o-plugin-openpix-na-sua-inst%C3%A2ncia-woocommerce-utilizando-one-click">documentation</a>'
        );

        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'openpix-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __(
                    'Enable OpenPix Boleto',
                    'openpix-for-woocommerce'
                ),
                'default' => 'no',
                'description' => "<p>$webhookLabel</p><p>$registerLabel</p><p>$documentationLabel</p>",
            ],
            'api_section' => [
                'title' => __(
                    'OpenPix Integration API',
                    'openpix-for-woocommerce'
                ),
                'type' => 'title',
                'description' => sprintf(
                    /* translators: %s: link to API documentation */
                    __(
                        'Follow documentation to get your OpenPix AppID here %s.',
                        'openpix-for-woocommerce'
                    ),
                    '<a target="_blank" href="https://developers.woovi.com/docs/apis/api-getting-started/">' .
                        __(
                            'OpenPix API Getting Started',
                            'openpix-for-woocommerce'
                        ) .
                        '</a>'
                ),
            ],
            'environment' => [
                'title' => __('Ambiente', 'openpix-for-woocommerce'),
                'type' => 'select',
                'description' => __(
                    'Selecione o ambiente de integração',
                    'openpix-for-woocommerce'
                ),
                'default' => 'prod',
                'options' => [
                    'sandbox-prod' => 'Sandbox',
                    'prod' => 'Production',
                ],
            ],
            'oneclick_section' => [
                'title' => __(
                    'Authenticate on the platform using 1 click',
                    'openpix-for-woocommerce'
                ),
                'type' => 'title',
            ],
            'oneclick_button' => [
                'type' => 'button',
                'title' => __(
                    'One Click Configuration',
                    'openpix-for-woocommerce'
                ),
                'class' => 'button-primary',
                'description' => sprintf(
                    __(
                        'By pressing this button, you will be redirected to our platform where we will quickly configure a new integration.',
                        'openpix-for-woocommerce'
                    )
                ),
            ],
            'appID' => [
                'title' => __('AppID OpenPix', 'openpix-for-woocommerce'),
                'type' => 'text',
                'description' => 'AppID OpenPix',
                'default' => '',
            ],
            'title' => [
                'title' => __('Title', 'openpix-for-woocommerce'),
                'type' => 'text',
                'description' => __(
                    'This controls the title which the user sees during checkout.',
                    'openpix-for-woocommerce'
                ),
                'default' => __('OpenPix Boleto', 'openpix-for-woocommerce'),
                'desc_tip' => true,
            ],
            'description' => [
                'title' => __('Description', 'openpix-for-woocommerce'),
                'type' => 'textarea',
                'description' => __(
                    'This controls the description which the user sees during checkout.',
                    'openpix-for-woocommerce'
                ),
                'default' => __(
                    'Pay with OpenPix Boleto',
                    'openpix-for-woocommerce'
                ),
            ],
            'order_button_text' => [
                'title' => __('Order Button Text', 'openpix-for-woocommerce'),
                'type' => 'text',
                'description' => __(
                    'This controls the text which the user sees during checkout.',
                    'openpix-for-woocommerce'
                ),
                'default' => __('Place Order', 'openpix-for-woocommerce'),
                'desc_tip' => true,
            ],
            'days_after_due_date' => [
                'title' => __('Days After Due Date', 'openpix-for-woocommerce'),
                'type' => 'number',
                'description' => __(
                    'Number of days after expiration that the boleto can still be paid.',
                    'openpix-for-woocommerce'
                ),
                'default' => 0,
            ],
            'interest_value' => [
                'title' => __(
                    'Interest (Juros) in Basis Points',
                    'openpix-for-woocommerce'
                ),
                'type' => 'number',
                'description' => __(
                    'Interest value in basis points (e.g., 100 = 1%).',
                    'openpix-for-woocommerce'
                ),
                'default' => 0,
            ],
            'fines_value' => [
                'title' => __(
                    'Fines (Multa) in Basis Points',
                    'openpix-for-woocommerce'
                ),
                'type' => 'number',
                'description' => __(
                    'Fines value in basis points (e.g., 200 = 2%).',
                    'openpix-for-woocommerce'
                ),
                'default' => 0,
            ],
            'status_when_waiting' => [
                'title' => __(
                    'Change status after issuing the boleto to',
                    'openpix-for-woocommerce'
                ),
                'type' => 'select',
                'options' => wc_get_order_statuses(),
                'default' => 'wc-pending',
            ],
            'status_when_paid' => [
                'title' => __(
                    'Order status after boleto charge is paid',
                    'openpix-for-woocommerce'
                ),
                'type' => 'select',
                'options' => wc_get_order_statuses(),
                'default' => 'wc-processing',
            ],
            'redirect_url_after_paid' => [
                'title' => __(
                    'Redirect URL after paid',
                    'openpix-for-woocommerce'
                ),
                'type' => 'text',
                'description' => __(
                    'Redirect URL after paid',
                    'openpix-for-woocommerce'
                ),
                'default' => '',
            ],
        ];
    }

    public function process_admin_options()
    {
        $old_environment = $this->get_option('environment');
        $saved = parent::process_admin_options();

        if ($saved) {
            $new_environment = $this->get_option('environment');

            if ($old_environment !== $new_environment) {
                $this->update_option('environment', $new_environment);
                $this->config = ConfigFactory::createStrategy($new_environment);
            }
        }

        return $saved;
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        // Check if transaction already exists
        $existing_correlation_id = $order->get_meta('openpix_correlation_id');
        $existing_transaction = $order->get_meta('openpix_transaction');

        if (!empty($existing_correlation_id) && !empty($existing_transaction)) {
            return [
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            ];
        }

        // Validate Address for Boleto
        $customerData = $this->getCustomerData($order);
        if (!$customerData || !isset($customerData['address'])) {
            wc_add_notice(
                __(
                    'Address is required for Boleto payment. Please check your billing address.',
                    'openpix-for-woocommerce'
                ),
                'error'
            );
            return ['result' => 'fail'];
        }

        $correlationID = $this->generate_correlation_id($order);
        $url = $this->config->getApiUrl() . '/api/v1/charge';

        WC_OpenPix::debugJson('Boleto API URL:', $url);

        $cart_total = $this->get_order_total();
        $total_cents = $this->get_openpix_amount($cart_total);

        $payload = [
            'correlationID' => $correlationID,
            'value' => $total_cents,
            'type' => 'BOLETO',
            'comment' => 'Order #' . $order_id,
            'customer' => $customerData,
            'additionalInfo' => [['key' => 'Order', 'value' => $order_id]],
        ];

        // Add Interest and Fines if configured
        $interest = $this->get_option('interest_value');
        if (!empty($interest) && $interest > 0) {
            $payload['interests'] = ['value' => (int) $interest];
        }

        $fines = $this->get_option('fines_value');
        if (!empty($fines) && $fines > 0) {
            $payload['fines'] = ['value' => (int) $fines];
        }

        $daysAfterDueDate = $this->get_option('days_after_due_date');
        if (!empty($daysAfterDueDate) && $daysAfterDueDate > 0) {
            $payload['daysAfterDueDate'] = (int) $daysAfterDueDate;
        }

        $order->update_meta_data('openpix_correlation_id', $correlationID);
        $order->save();

        $params = [
            'timeout' => 120,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $this->appID,
                'version' => WC_OpenPix::VERSION,
                'platform' => 'WOOCOMMERCE',
            ],
            'body' => wp_json_encode($payload),
            'method' => 'POST',
            'data_format' => 'body',
        ];

        if ($this->config->getEnv() === 'development') {
            $response = wp_remote_post($url, $params);
        } else {
            $response = wp_safe_remote_post($url, $params);
        }

        if (is_wp_error($response)) {
            WC_OpenPix::debugJson('Error creating Boleto (WP Error):', [
                'error' => $response->get_error_message(),
                'payload' => $payload,
            ]);
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is sanitized by WooCommerce
            throw new \Exception($response->get_error_message());
        }

        $body = json_decode($response['body'], true);

        if (isset($body['error']) || isset($body['errors'])) {
            WC_OpenPix::debugJson('Error creating Boleto (API Error):', [
                'response' => $body,
                'payload' => $payload,
            ]);

            $errorMessage = $body['error'] ?? 'Unknown error';
            if (isset($body['errors']) && is_array($body['errors'])) {
                $errorMessage .= ' ' . wp_json_encode($body['errors']);
            }

            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is sanitized by WooCommerce
            throw new \Exception($errorMessage);
        }

        // Save transaction data
        $meta_data = [
            'openpix_correlation_id' => $correlationID,
            'openpix_transaction' => [
                'paymentLinkUrl' => $body['charge']['paymentLinkUrl'],
                'qrCodeImage' => $body['charge']['qrCodeImage'],
                'brCode' => $body['charge']['brCode'],
                'boletoBarcode' =>
                    $body['charge']['paymentMethods']['boleto'][
                        'boletoBarcode'
                    ] ?? '',
                'boletoDigitable' =>
                    $body['charge']['paymentMethods']['boleto'][
                        'boletoDigitable'
                    ] ?? '',
                'boletoPdf' =>
                    $body['charge']['paymentMethods']['boleto'][
                        'barcodeImage'
                    ] ?? '',
            ],
        ];

        foreach ($meta_data as $key => $value) {
            $order->update_meta_data($key, $value);
        }

        $order->update_status(
            $this->status_when_waiting,
            __(
                'Boleto generated. Waiting for payment.',
                'openpix-for-woocommerce'
            )
        );

        WC()->cart->empty_cart();

        return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }

    public function get_openpix_amount($total)
    {
        return absint(
            wc_format_decimal((float) $total * 100, wc_get_price_decimals())
        );
    }

    public function generate_correlation_id($order)
    {
        return $order->get_order_key() . '-' . $order->get_id();
    }

    public function formatPhone($phone)
    {
        $cleanNumber = preg_replace('/\D+/', '', $phone);

        // Check if libphonenumber exists or try to load it
        if (!class_exists('libphonenumber\PhoneNumberUtil')) {
            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                require_once __DIR__ . '/../vendor/autoload.php';
            }
        }

        if (class_exists('libphonenumber\PhoneNumberUtil')) {
            $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
            try {
                $numberProto = $phoneUtil->parse($cleanNumber, 'BR');
                if ($phoneUtil->isValidNumber($numberProto)) {
                    $formattedNumber = $phoneUtil->format(
                        $numberProto,
                        \libphonenumber\PhoneNumberFormat::E164
                    );
                    return ltrim($formattedNumber, '+');
                }
            } catch (\libphonenumber\NumberParseException $e) {
                // Ignore error and fall through to manual formatting
            }
        }

        // Manual fallback formatting for Brazil (BR)
        // Assuming input might be like 11999999999 or 5511999999999
        if (strlen($cleanNumber) >= 10 && strlen($cleanNumber) <= 11) {
            // Add Brazil country code if missing
            return '55' . $cleanNumber;
        }

        if (strlen($cleanNumber) >= 12 && substr($cleanNumber, 0, 2) === '55') {
            return $cleanNumber;
        }

        return $cleanNumber; // Return cleaned number as best effort
    }

    public function getCustomerData($order)
    {
        $order_data = $order->get_data();
        $name =
            $order_data['billing']['first_name'] .
            ' ' .
            $order_data['billing']['last_name'];
        $email = $order_data['billing']['email'];
        $phone = $order_data['billing']['phone'];

        $taxID = '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified by WooCommerce
        if (isset($_POST['openpix_customer_taxid'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $taxID = sanitize_text_field(
                wp_unslash($_POST['openpix_customer_taxid'])
            );
        } else {
            // Try to get from raw input (for Blocks)
            $raw_input = file_get_contents('php://input');
            $data = json_decode($raw_input, true);
            if (isset($data['payment_data'])) {
                foreach ($data['payment_data'] as $payment_datum) {
                    if ($payment_datum['key'] === 'taxID') {
                        $taxID = sanitize_text_field($payment_datum['value']);
                        break;
                    }
                }
            }

            if (empty($taxID)) {
                $cpf = $order->get_meta('_billing_cpf');
                $cnpj = $order->get_meta('_billing_cnpj');
                $taxID = !empty($cpf) ? $cpf : $cnpj;
            }
        }

        $customer = [
            'name' => $name,
            'email' => $email,
            'taxID' => $taxID,
        ];

        $formattedPhone = $this->formatPhone($phone);
        if ($formattedPhone) {
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
        return !empty($address['zipcode']) &&
            !empty($address['street']) &&
            !empty($address['city']) &&
            !empty($address['state']);
    }

    public function thankyou_page($order_id)
    {
        $order = wc_get_order($order_id);

        if ($order->get_payment_method() !== $this->id) {
            return;
        }

        $correlationID = $order->get_meta('openpix_correlation_id');
        $environment = $this->config->getEnv();
        $pluginUrl = $this->config->getPluginUrl();
        $queryString = "appID={$this->appID}&correlationID={$correlationID}&node=openpix-order";
        $src = "$pluginUrl?$queryString";

        wc_get_template(
            'payment-instructions.php',
            [
                'correlationID' => $correlationID,
                'environment' => $environment,
                'appID' => $this->appID,
                'pluginUrl' => WC_OpenPix::get_assets_url(),
                'src' => $src,
            ],
            WC_OpenPix::get_templates_path(),
            WC_OpenPix::get_templates_path()
        );
    }

    public function afterOrderDetailHook($order)
    {
        // $this->thankyou_page($order->get_id());
    }

    /**
     * Handles incoming IPN (Instant Payment Notification) requests.
     *
     * This is the main entry point for the IPN requests.
     *
     * @return void
     */
    public function webhook()
    {
        @ob_clean();
        $body = file_get_contents('php://input', true);
        $data = json_decode($body, true);

        WC_OpenPix::debug('Boleto Webhook received');
        WC_OpenPix::debugJson('Boleto Webhook data', $data);

        $this->validateWebhook($data, $body);

        $this->handleWebhookEvents($data, $body);
    }

    public function validateWebhook($data, $body)
    {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Signature is used for cryptographic verification only
        $signature = isset($_SERVER['HTTP_X_WEBHOOK_SIGNATURE'])
            ? sanitize_text_field(
                wp_unslash($_SERVER['HTTP_X_WEBHOOK_SIGNATURE'])
            )
            : null;

        if (!$signature || !$this->validSignature($body, $signature)) {
            WC_OpenPix::debug('Boleto Webhook: Invalid Signature');
            header('HTTP/1.2 400 Bad Request');
            $response = [
                'error' => 'Invalid Webhook signature',
            ];
            echo wp_json_encode($response);
            exit();
        }

        if (!$this->isValidWebhookPayload($data)) {
            WC_OpenPix::debug('Boleto Webhook: Invalid Payload');
            header('HTTP/1.2 400 Bad Request');
            $response = [
                'error' => 'Invalid Webhook Payload',
            ];
            echo wp_json_encode($response);
            exit();
        }

        if ($this->isPixDetachedPayload($data)) {
            WC_OpenPix::debug('Boleto Webhook: Pix Detached');
            header('HTTP/1.1 200 OK');

            $response = [
                'message' => 'Pix Detached',
            ];
            echo wp_json_encode($response);
            exit();
        }
    }

    public function validSignature($payload, $signature)
    {
        $publicKey = base64_decode($this->config->getPublicKeyBase64());

        $verify = openssl_verify(
            $payload,
            base64_decode($signature),
            $publicKey,
            'sha256WithRSAEncryption'
        );

        return $verify === 1 ? true : false;
    }

    public function isValidWebhookPayload($data)
    {
        if (!isset($data['event']) && !isset($data['evento'])) {
            return false;
        }

        return true;
    }

    public function isPixDetachedPayload($data)
    {
        if (!isset($data['pix'])) {
            return false;
        }

        $pix = $data['pix'];
        return isset($pix['isDetached']) && $pix['isDetached'] === true;
    }

    public function handleWebhookEvents($data, $body)
    {
        $event = $data['evento'] ?? $data['event'];
        WC_OpenPix::debug('Boleto Webhook Event: ' . $event);

        if ($event === 'teste_webhook') {
            $this->handleTestWebhook($data);
        }

        if ($event === 'woocommerce-configure') {
            // Not applicable for Boleto gateway directly, but kept for compatibility if needed
            return;
        }

        if (
            $event === 'OPENPIX:TRANSACTION_RECEIVED' ||
            $event === 'OPENPIX:CHARGE_COMPLETED'
        ) {
            $this->handleWebhookOrderUpdate($data);
            return;
        }

        if ($event === 'OPENPIX:CHARGE_EXPIRED') {
            $this->handleWebhookChargeExpired($data);
            return;
        }
    }

    public function handleWebhookChargeExpired($data)
    {
        $correlationID = $data['charge']['correlationID'];
        $status = $data['charge']['status'];

        WC_OpenPix::debug('Boleto Webhook Charge Expired: ' . $correlationID);

        $order = $this->get_order_by_correlation_id($correlationID);

        if (!$order) {
            WC_OpenPix::debug(
                'Boleto Webhook: Order not found for correlationID ' .
                    $correlationID
            );
            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' => 'order not found',
                'correlationId' => $correlationID,
                'status' => $status,
            ];
            echo wp_json_encode($response);
            exit();
        }

        $order->update_status(
            'cancelled',
            __('OpenPix: Boleto expired.', 'openpix-for-woocommerce')
        );

        header('HTTP/1.1 200 OK');
        $response = [
            'message' => 'success',
            'order_id' => $order->get_id(),
        ];
        echo wp_json_encode($response);
        exit();
    }

    public function handleTestWebhook($data)
    {
        header('HTTP/1.1 200 OK');
        $response = [
            'message' => 'success',
        ];
        echo wp_json_encode($response);
        exit();
    }

    public function handleWebhookOrderUpdate($data)
    {
        $correlationID = $data['charge']['correlationID'];
        $status = $data['charge']['status'];

        WC_OpenPix::debug(
            'Boleto Webhook Order Update: ' .
                $correlationID .
                ' Status: ' .
                $status
        );

        // Boleto might not have endToEndId immediately or in the same way as Pix, but let's check
        $endToEndId = $data['pix']['endToEndId'] ?? null;

        $order = $this->get_order_by_correlation_id($correlationID);

        if (!$order) {
            WC_OpenPix::debug(
                'Boleto Webhook: Order not found for correlationID ' .
                    $correlationID
            );
            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' => 'order not found',
                'correlationId' => $correlationID,
                'status' => $status,
            ];
            echo wp_json_encode($response);
            exit();
        }

        $order_correlation_id = $order->get_meta('openpix_correlation_id');
        $order_end_to_end_id = $order->get_meta('openpix_endToEndId');

        if ($order_end_to_end_id) {
            WC_OpenPix::debug('Boleto Webhook: Order already has endToEndId');
            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' => 'order already with end to end id',
                'order_id' => $order->get_id(),
                'correlationId' => $correlationID,
                'status' => $status,
            ];
            echo wp_json_encode($response);
            exit();
        }

        if (!$order_correlation_id) {
            WC_OpenPix::debug(
                'Boleto Webhook: Order missing correlationID meta'
            );
            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' => 'order without correlation id',
                'order_id' => $order->get_id(),
                'correlationId' => $correlationID,
                'status' => $status,
            ];
            echo wp_json_encode($response);
            exit();
        }

        if ($order_correlation_id !== $correlationID) {
            WC_OpenPix::debug('Boleto Webhook: CorrelationID mismatch');
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
            echo wp_json_encode($response);
            exit();
        }

        $order_status = $order->get_status();

        // Check if already paid
        $statuses = [
            'processing',
            'completed',
            'wc-processing',
            'wc-completed',
        ];
        if (
            in_array($order_status, $statuses) ||
            in_array('wc-' . $order_status, $statuses)
        ) {
            WC_OpenPix::debug('Boleto Webhook: Order already paid');
            header('HTTP/1.1 200 OK');
            echo wp_json_encode([
                'message' => 'success',
                'info' => 'Order already paid',
            ]);
            exit();
        }

        if ($status === 'COMPLETED') {
            WC_OpenPix::debug('Boleto Webhook: Marking order as paid');
            $order->update_status(
                $this->status_when_paid,
                __('OpenPix: Boleto paid', 'openpix-for-woocommerce')
            );

            $order->payment_complete();

            if ($endToEndId) {
                $order->update_meta_data('openpix_endToEndId', $endToEndId);
            }

            $order->save();
        }

        header('HTTP/1.1 200 OK');
        $response = [
            'message' => 'success',
            'order_id' => $order->get_id(),
            'correlationId' => $correlationID,
            'status' => $status,
        ];

        echo wp_json_encode($response);
        exit();
    }

    public function get_order_by_correlation_id($correlationID)
    {
        $args = [
            'meta_key' => 'openpix_correlation_id',
            'meta_value' => $correlationID,
            'post_type' => 'shop_order',
            'post_status' => 'any',
            'numberposts' => 1,
        ];

        $posts = get_posts($args);

        if (empty($posts)) {
            return null;
        }

        return wc_get_order($posts[0]->ID);
    }
}
