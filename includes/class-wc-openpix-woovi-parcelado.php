<?php

if (!defined('ABSPATH')) {
    exit();
}

require_once 'config/config.php';
require_once 'class-wc-openpix-pix.php';

class WC_OpenPix_Pix_Parcelado_Gateway extends WC_Payment_Gateway
{
    public $appID;
    public $status_when_waiting;
    public $status_when_paid;

    public function __construct()
    {
        WC_OpenPix::debugJson('construct', 1);
        $this->id = 'woocommerce_openpix_pix_parcelado';
        $this->method_title = __('OpenPix Parcelado', 'woocommerce-openpix');
        $this->method_description = __(
            'OpenPix Parcelado is an innovative payment method that allows your customers to pay a 50% down payment for the Pix and divide the remainder of the purchase up to 4x on the card. The process is completely online, fast and easy, with a native anti-fraud system for total security.',
            'woocommerce-openpix'
        );
        $this->has_fields = true; // direct payment
        $this->supports = ['products', 'refunds'];

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        $this->order_button_text = $this->get_option('order_button_text');

        $this->status_when_waiting = $this->get_option('status_when_waiting');
        $this->status_when_paid = $this->get_option('status_when_paid');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [
            $this,
            'process_admin_options',
        ]);
        add_action('woocommerce_thankyou_' . $this->id, [
            $this,
            'thankyou_page',
        ]);

        // inject openpix react
        add_action('wp_enqueue_scripts', [$this, 'checkout_scripts']);

        add_action('woocommerce_after_order_details', [
            $this,
            'afterOrderDetailHook',
        ]);

        // $this->registerHooks();
    }

    public function can_refund_order($order)
    {
        return true;
    }

    public function process_refund($order_id, $amount = null, $reason = null)
    {
        $order = wc_get_order($order_id);

        $chargeCorrelationID = get_post_meta(
            $order->id,
            'openpix_correlation_id',
            true
        );

        $url =
            OpenPixConfig::getApiUrl() .
            "/api/v1/charge/$chargeCorrelationID/refund";

        $total_cents = $this->get_openpix_amount($amount);

        $payload = [
            'correlationID' => WC_OpenPix::uuid_v4(),
            'value' => $total_cents,
            'comment' => $reason,
        ];

        $wc_openpix_pix_gateway = new WC_OpenPix_Pix_Gateway();

        $params = [
            'timeout' => 60,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $wc_openpix_pix_gateway->appID,
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

    // giftback
    public function checkout_scripts()
    {
        if (is_checkout()) {
            $name = get_bloginfo('name');

            $correlationID = $this->getCorrelationID();

            WC_OpenPix::debug('get correlationID result ' . $correlationID);

            $wc_openpix_pix_gateway = new WC_OpenPix_Pix_Gateway();

            wp_localize_script('openpix-checkout', 'wcOpenpixParams', [
                'appID' => $wc_openpix_pix_gateway->appID,
                'storeName' => $name,
                'correlationID' => $correlationID,
            ]);
        }
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
        $documentationLabel = sprintf(
            __(
                'See more %s',
                'woocommerce-openpix'
            ),
            '<a target="_blank" href="https://woovi.com/pix/woovi-parcelado">here</a>'
        );

        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'woocommerce-openpix'),
                'type' => 'checkbox',
                'label' => __('Enable OpenPix', 'woocommerce-openpix'),
                'default' => 'no',
                'description' => "<p>$documentationLabel</p>",
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
                'default' => __('Pay with Pix and Credit Card', 'woocommerce-openpix'),
            ],
            'description' => [
                'title' => __('Description', 'woocommerce-openpix'),
                'type' => 'text',
                'description' => __(
                    'This controls the description which the user sees during checkout.',
                    'woocommerce-openpix'
                ),
                'desc_tip' => true,
                'default' => __('Pay with Pix and Credit Card', 'woocommerce-openpix'),
            ],
            'order_button_text' => [
                'title' => __('Order Button Text', 'woocommerce-openpix'),
                'type' => 'text',
                'description' => __(
                    'This controls the order button payment label.',
                    'woocommerce-openpix'
                ),
                'desc_tip' => true,
                'default' => __('Pay with Pix and Credit Card', 'woocommerce-openpix'),
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

        $wc_openpix_pix_gateway = new WC_OpenPix_Pix_Gateway();

        $params = [
            'timeout' => 60,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $wc_openpix_pix_gateway->getAppID(),
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
    public function thankyou_page($order_id)
    {
        $data = $this->getPluginSrc($order_id);
        $wc_openpix_pix_gateway = new WC_OpenPix_Pix_Gateway();

        wc_get_template(
            'payment-instructions.php',
            [
                'paymentLinkUrl' => $data['orderData']['paymentLinkUrl'],
                'qrCodeImage' => $data['orderData']['qrCodeImage'],
                'brCode' => $data['orderData']['brCode'],
                'correlationID' => $data['correlationID'],
                'environment' => $data['environment'],
                'appID' => $wc_openpix_pix_gateway->appID,
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
        $wc_openpix_pix_gateway = new WC_OpenPix_Pix_Gateway();

        $queryString = "appID={$wc_openpix_pix_gateway->appID}&correlationID={$correlationID}&node=openpix-order";
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
            'type' => 'PIX_CREDIT'
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

            $wc_openpix_pix_gateway = new WC_OpenPix_Pix_Gateway();

            $params = [
                'timeout' => 60,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => $wc_openpix_pix_gateway->appID,
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