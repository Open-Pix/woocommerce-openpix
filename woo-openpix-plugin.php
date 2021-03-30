<?php

/*
 * Plugin Name: Woocommerce OpenPix Plugin
 * Description: Woocommerce OpenPix Plugin
 * Version: 1.0.0
 */

if (!defined("ABSPATH")) {
    exit(); // show nothing if someone open this file directly
}

/**
 * Check if WooCommerce is active
 **/
if (
    in_array(
        "woocommerce/woocommerce.php",
        apply_filters("active_plugins", get_option("active_plugins"))
    )
) {
    define("WOO_OPENPIX_PLUGIN", untrailingslashit(plugin_dir_path(__FILE__)));
    define("WOO_OPENPIX_PLUGIN_ARQUIVO", __FILE__);

    // init plugin
    add_action("plugins_loaded", "woo_openpix_plugin_init", 0);
}

function get_templates_path()
{
    return plugin_dir_path(__FILE__) . "templates/";
}


function woo_openpix_plugin_init()
{
    function debug($message) {
        $logger = wc_get_logger();
        $context = array(
            'source'  => 'wc_openpix',
        );
        $logger->debug($message, $context);
    }

    class Woo_OpenPix_Gateway extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id = "woo_openpix_plugin";
            $this->method_title = "Pagar com OpenPix";
            $this->method_description = "WooCommerce OpenPix Payment Gateway";
            $this->title = "Pagar com OpenPix";
            $this->order_button_text = "Pagar com OpenPix";

            $this->has_fields = true; // direct payment

            $this->supports = ["products"];

            $this->init_form_fields();
            $this->init_settings();

            add_action(
                "woocommerce_update_options_payment_gateways_" . $this->id,
                [$this, "process_admin_options"]
            );

            // inject openpix react
            add_action("wp_enqueue_scripts", [$this, "checkout_scripts"]);
        }

        public function checkout_scripts()
        {
            if (is_checkout()) {
                $reactDirectory = join(DIRECTORY_SEPARATOR, [
                    plugin_dir_url(__FILE__),
                    "openpix-react-plugin",
                    "build",
                ]);

                                wp_enqueue_script(
                                    "openpix-checkout2",
                                    $reactDirectory . "/main.js",
                                    [], null
                                );

//                wp_enqueue_script(
//                    "openpix-checkout",
//                    plugin_dir_url(__FILE__) . "assets/js/checkout.js",
//                    ["jquery"],
//                    "1.0.0",
//                    true
//                );
                debug('enqueue reeact scripts');
                debug($reactDirectory . "/main.js");
            }
        }

        public function init_form_fields()
        {
            $this->form_fields = [
                "AppID" => [
                    "title" => "AppID OpenPix",
                    "type" => "text",
                    "description" => "AppID OpenPix",
                    "default" => "",
                ],
            ];
        }

        public function payment_fields()
        {
            if ($description = $this->get_description()) {
                echo wp_kses_post(wpautop(wptexturize($description)));
            }

            debug('payment fields');
            debug(print_r($description));
            debug(print_r($this->get_description()));
        }

        public function process_payment($order_id)
        {
            global $woocommerce;
            $order2 = new WC_Order( $order_id );

            $order = wc_get_order( $order_id );

            debug('process payment');
            debug(print_r($order, true));
            debug(print_r($order2, true));

            $woocommerce->cart->empty_cart();

            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $order )
            );
        }
    }

    //cria o gateway
    function woocommerce_add_woo_openpix_plugin($methods)
    {
        $methods[] = "Woo_OpenPix_Gateway";
        return $methods;
    }
    add_filter(
        "woocommerce_payment_gateways",
        "woocommerce_add_woo_openpix_plugin"
    );
}
