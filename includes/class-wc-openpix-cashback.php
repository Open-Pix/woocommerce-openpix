<?php

if (!defined('ABSPATH')) {
    exit();
}

class WC_OpenPix_Cashback_Gateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'woocommerce_openpix';
        $this->method_title = __('OpenPix - Cashback', 'woocommerce-openpix');
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

        $this->webhookTrack =
            'https://webhook.site/874f0614-2057-413f-9bbd-934d6b290a1c';
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

    public function get_checkout_js_url()
    {
        return 'http://localhost:6688/main.js';
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

            $correlationID = $this->getCorrelationID();

            WC_OpenPix::debug('get correlationID result ' . $correlationID);

            wp_localize_script('openpix-checkout', 'wcOpenpixParams', [
                'appID' => $this->appID,
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
        $this->registerHooks();
    }

    public function is_available()
    {
        return parent::is_available() && !empty($this->appID);
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

    public function ceHandlerWooCommerceNewOrder($order)
    {
        try {
            $params = [
                'timeout' => 60,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => $this->appID,
                    'version' => WC_OpenPix::VERSION,
                    'platform' => 'WOOCOMMERCE',
                ],
                'body' => json_encode(json_decode($order, true)),
                'method' => 'POST',
                'data_format' => 'body',
            ];
            wp_remote_post($this->webhookTrack, $params);
        } catch (\Exception $exception) {
        }
    }

    public function registerHooks()
    {
        add_action('woocommerce_checkout_order_created', [
            $this,
            'ceHandlerWooCommerceNewOrder',
        ]);
    }
}
