<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function debug($message)
{
    $logger = wc_get_logger();
    $context = [
        'source' => 'woocommerce_openpix',
    ];
    $logger->debug($message, $context);
}

class WC_OpenPix_Gateway extends WC_Payment_Gateway
{
    const VERSION = '1.0.0';

    public function __construct()
    {
        $this->id = 'woocommerce_openpix';
        $this->method_title = 'Pagar com OpenPix';
        $this->method_description = 'WooCommerce OpenPix Payment Gateway';
        $this->title = 'Pagar com OpenPix';
        $this->order_button_text = 'Pagar com OpenPix';

        $this->has_fields = true; // direct payment

        $this->supports = ['products'];

        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->appID = $this->get_option('appID');

        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            [$this, 'process_admin_options']
        );

        // inject openpix react
        add_action('wp_enqueue_scripts', [$this, 'checkout_scripts']);
    }

    public function get_checkout_js_url() {
        if (OPENPIX_ENV === 'development') {
            return plugins_url(
                'build/main.js',
                plugin_dir_path( __FILE__ ));
        }

        if (OPENPIX_ENV === 'staging') {
            return plugins_url(
                'assets/js/woo-openpix-dev.js',
                plugin_dir_path( __FILE__ ));
        }

        // production
        return plugins_url(
            'assets/js/woo-openpix.js',
            plugin_dir_path( __FILE__ ));
    }

    public function checkout_scripts()
    {
        if (is_checkout()) {
            $reactDirectory = join(DIRECTORY_SEPARATOR, [
                plugin_dir_url(__FILE__),
                'build',
            ]);

            debug($this->get_checkout_js_url());

            wp_enqueue_script(
                'openpix-checkout',
                $this->get_checkout_js_url(),
                ['jquery', 'jquery-blockui'],
                WC_OpenPix_Gateway::VERSION,
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
                'title' => 'AppID OpenPix',
                'type' => 'text',
                'description' => 'AppID OpenPix',
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

    public function process_payment($order_id)
    {
        wc_add_notice('not implemented', 'error');

        return [
            'result' => 'fail',
        ];

        global $woocommerce;

        $order = wc_get_order($order_id);

        debug('process payment');
        debug(print_r($order, true));

        $woocommerce->cart->empty_cart();

        return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }
}