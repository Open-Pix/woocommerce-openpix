<?php
/**
 * Plugin Name: OpenPix for WooCommerce
 * Description: Gateway de pagamento OpenPix para WooCommerce
 * Author: OpenPix
 * Author URI: https://openpix.com.br/
 * Version: 1.0.0
 * Text Domain: woocommerce-openpix
 *
 * @package WooCommerce_OpenPix
 */

if (!defined('ABSPATH')) {
    exit(); // show nothing if someone open this file directly
}

/**
 * Check if WooCommerce is active
 **/
if (
    in_array(
        'woocommerce/woocommerce.php',
        apply_filters('active_plugins', get_option('active_plugins'))
    )
) {
    define(
        'WOOCOMMERCE_OPENPIX_PLUGIN',
        untrailingslashit(plugin_dir_path(__FILE__))
    );
    define('WOOCOMMERCE_OPENPIX_PLUGIN_ARQUIVO', __FILE__);

    // init plugin
    add_action('plugins_loaded', 'woocommerce_openpix_init', 0);
}

function get_templates_path()
{
    return plugin_dir_path(__FILE__) . 'templates/';
}

function woocommerce_openpix_init()
{
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

        public function checkout_scripts()
        {
            if (is_checkout()) {
                $reactDirectory = join(DIRECTORY_SEPARATOR, [
                    plugin_dir_url(__FILE__),
                    'build',
                ]);

                wp_enqueue_script(
                    'openpix-checkout',
                    $reactDirectory . '/main.js',
                    ['jquery', 'jquery-blockui'],
                    WC_OpenPix_Gateway::VERSION,
                    true
                );

                wp_localize_script('openpix-checkout', 'wcOpenpixParams', [
                    'appID' => $this->appID,
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

            echo '<div></div>';
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

    //cria o gateway
    function woocommerce_add_openpix($methods)
    {
        $methods[] = 'WC_OpenPix_Gateway';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_openpix');
}
