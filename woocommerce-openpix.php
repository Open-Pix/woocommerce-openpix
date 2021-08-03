<?php
/**
 * Plugin Name: OpenPix for WooCommerce
 * Description: WooCommerce OpenPix Payment Gateway
 * Author: OpenPix
 * Author URI: https://openpix.com.br/
 * Version: 1.6.0
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
    // init plugin
    add_action('plugins_loaded', 'woocommerce_openpix_init', 0);
}

function woocommerce_openpix_init()
{
    WC_OpenPix::get_instance();
}

class WC_OpenPix
{
    const VERSION = '1.6.0';
    // change this to work in development, staging or production
    //            const OPENPIX_ENV = 'development';
    //    const OPENPIX_ENV = 'staging';
    const OPENPIX_ENV = 'production';

    protected static $instance = null;

    private function __construct()
    {
        // Check if WooCommerce exist
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }

        $this->includes();

        add_filter('woocommerce_payment_gateways', [$this, 'add_gateway']);
        add_action('wp_enqueue_scripts', [$this, 'load_plugin_assets']);
    }

    public static function get_instance()
    {
        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function includes()
    {
        include_once dirname(__FILE__) . '/includes/class-wc-openpix-pix.php';
    }

    public function add_gateway($methods)
    {
        $methods[] = 'WC_OpenPix_Pix_Gateway';

        return $methods;
    }

    public static function get_plugin_path()
    {
        return plugin_dir_path(__FILE__);
    }

    public static function get_templates_path()
    {
        return self::get_plugin_path() . 'templates/';
    }

    public static function debug($message)
    {
        $logger = wc_get_logger();
        $context = [
            'source' => 'woocommerce_openpix',
        ];
        $logger->debug($message, $context);
    }

    public static function uuid_v4()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    function get_assets_url()
    {
        return plugin_dir_url(dirname(__FILE__)) . 'assets/';
    }

    // load javascript and css
    public function load_plugin_assets()
    {
        wp_register_script(
            'openpix_frontend_js',
            wc_openpix_assets_url() . 'thankyou.js',
            ['jquery'],
            '1.0',
            false
        );
        wp_register_style(
            'openpix_frontend_css',
            wc_openpix_assets_url() . 'thankyou.css',
            '',
            '1.0',
            false
        );

        // add script and style to screen
        wp_enqueue_script('openpix_frontend_js');
        wp_enqueue_style('openpix_frontend_css');
    }
}
