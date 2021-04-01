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

// change this to work in development, staging or production
define(OPENPIX_ENV, 'development');
//define(OPENPIX_ENV, 'staging');
//define(OPENPIX_ENV, 'production');

/**
 * Check if WooCommerce is active
 **/
if (
    in_array(
        'woocommerce/woocommerce.php',
        apply_filters('active_plugins', get_option('active_plugins'))
    )
) {
    // init plugin
    add_action('plugins_loaded', 'woocommerce_openpix_init', 0);
}

function get_templates_path()
{
    return plugin_dir_path(__FILE__) . 'templates/';
}

function woocommerce_openpix_init()
{
    if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

    // WooCommerce exist
    if ( !class_exists( 'WC_OpenPix_Gateway' ) ) {
        include_once dirname( __FILE__ ) . '/includes/class-wc-openpix.php';
    }

    //cria o gateway
    function woocommerce_add_openpix($methods)
    {
        $methods[] = 'WC_OpenPix_Gateway';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_openpix');
}
