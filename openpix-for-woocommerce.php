<?php

/**
 * Plugin Name: OpenPix for WooCommerce
 * Description: Accept Pix payments with real-time updates, seamless checkout, and automatic order status updates.
 * Author: OpenPix
 * Author URI: https://openpix.com.br/
 * Version: 2.13.6
 * Text Domain: openpix-for-woocommerce
 * WC tested up to: 8.2.2
 * Requires Plugins: woocommerce
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package OpenPix_For_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit(); // show nothing if someone open this file directly
}

/**
 * Check if WooCommerce is active
 */
if (
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Using WordPress core filter.
    in_array(
        'woocommerce/woocommerce.php',
        apply_filters( 'active_plugins', get_option( 'active_plugins' ) ),
        true
    )
) {
    // declare compatibility with HPOS before all
    add_action('before_woocommerce_init', function () {
        if (
            class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)
        ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                __FILE__,
                true
            );

            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'cart_checkout_blocks',
                __FILE__,
                true
            );
        }
    });

    // init plugin
    add_action('plugins_loaded', 'woocommerce_openpix_init', 0);
}

function woocommerce_openpix_init()
{
    WC_OpenPix::get_instance();
}

class WC_OpenPix
{
    const VERSION = '2.13.6';

    protected static $instance = null;

    private function __construct()
    {
        // Check if WooCommerce exist
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }

        $this->includes();
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [
            $this,
            'plugin_action_links',
        ]);

        add_filter('woocommerce_payment_gateways', [$this, 'add_gateway']);
        add_action('wp_enqueue_scripts', [$this, 'load_plugin_assets']);
        add_action('woocommerce_blocks_loaded', [$this, 'add_gateway_blocks']);

        add_action(
            'woocommerce_before_checkout_form',
            'wc_openpix_user_notice_incompatibility_with_checkout_block'
        );
        add_action( 'admin_notices', 'wc_openpix_admin_notice_incompatibility_with_block' );
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
        // include_once dirname(__FILE__) .'/includes/class-wc-openpix-prod.php';
        include_once dirname(__FILE__) . '/includes/class-wc-openpix-pix.php';
        include_once dirname(__FILE__) .
            '/includes/class-wc-openpix-pix-parcelado.php';
        include_once dirname(__FILE__) .
            '/includes/class-wc-openpix-pix-crediary.php';
        include_once dirname(__FILE__) .
            '/includes/class-wc-openpix-boleto.php';
    }

    /**
     * Action links.
     *
     * @param  array $links Default plugin links.
     *
     * @return array
     */
    public function plugin_action_links($links)
    {
        $plugin_links = [];
        $plugin_links[] =
            '<a href="' .
            esc_url(
                admin_url(
                    'admin.php?page=wc-settings&tab=checkout&section=woocommerce_openpix_pix'
                )
            ) .
            '">' .
            __('Settings Pix', 'openpix-for-woocommerce') .
            '</a>';

        $plugin_links[] =
            '<a href="' .
            esc_url(
                admin_url(
                    'admin.php?page=wc-settings&tab=checkout&section=woocommerce_openpix_pix_parcelado'
                )
            ) .
            '">' .
            __('Settings Parcelado', 'openpix-for-woocommerce') .
            '</a>';

        $plugin_links[] =
            '<a href="' .
            esc_url(
                admin_url(
                    'admin.php?page=wc-settings&tab=checkout&section=woocommerce_openpix_pix_crediary'
                )
            ) .
            '">' .
            __('Settings Pix Crediary', 'openpix-for-woocommerce') .
            '</a>';

        $plugin_links[] =
            '<a href="' .
            esc_url(
                admin_url(
                    'admin.php?page=wc-settings&tab=checkout&section=woocommerce_openpix_boleto'
                )
            ) .
            '">' .
            __('Settings Boleto', 'openpix-for-woocommerce') .
            '</a>';

        $plugin_links[] =
            '<a  target="_blank" href="https://developers.openpix.com.br/docs/ecommerce/woocommerce/woocommerce-plugin">' .
            __('Documentation', 'openpix-for-woocommerce') .
            '</a>';

        $plugin_links[] =
            '<a  target="_blank" href="https://app.openpix.com/register/?src=wordpress"> ' .
            __('Sign up', 'openpix-for-woocommerce') .
            '</a>';

        return array_merge($plugin_links, $links);
    }

    public function add_gateway($methods)
    {
        // $methods[] = 'WC_OpenPix_Pix_Prod_Gateway';
        $methods[] = WC_OpenPix_Pix_Gateway::instance();
        // $methods[] = 'WC_OpenPix_Pix_Gateway';
        $methods[] = WC_OpenPix_Pix_Parcelado_Gateway::instance();
        $methods[] = WC_OpenPix_Pix_Crediary_Gateway::instance();
        $methods[] = WC_OpenPix_Boleto_Gateway::instance();

        return $methods;
    }

    public function add_gateway_blocks()
    {
        if (
            !class_exists(
                'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType'
            )
        ) {
            return;
        }

        include_once dirname(__FILE__) .
            '/includes/class-wc-openpix-pix-block.php';
        include_once dirname(__FILE__) .
            '/includes/class-wc-openpix-boleto-block.php';

        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function (
                Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry
            ) {
                $payment_method_registry->register(new WC_OpenPix_Pix_Block());
                $payment_method_registry->register(
                    new WC_OpenPix_Boleto_Block()
                );
            }
        );
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

    public static function debugJson($message, $objectToBeEncoded)
    {
        $logger = wc_get_logger();
        $context = [
            'source' => 'woocommerce_openpix',
        ];

        $jsonEncodedObject = wp_json_encode(
            $objectToBeEncoded,
            JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_NUMERIC_CHECK |
                JSON_PRETTY_PRINT
        );

        $logger->debug($message . "\n" . $jsonEncodedObject, $context);
    }

    public static function uuid_v4()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            wp_rand(0, 0xffff),
            wp_rand(0, 0xffff),

            // 16 bits for "time_mid"
            wp_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            wp_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            wp_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            wp_rand(0, 0xffff),
            wp_rand(0, 0xffff),
            wp_rand(0, 0xffff)
        );
    }

    public static function get_assets_url()
    {
        return plugins_url('', __FILE__);
    }

    // load javascript and css
    public function load_plugin_assets()
    {
        wp_register_style(
            'openpix_frontend_css',
            wc_openpix_assets_url() . 'thankyou.css',
            '',
            '1.0',
            false
        );

        // add script and style to screen
        wp_enqueue_style('openpix_frontend_css');
    }
}

/**
 * Check compatibility with WooCommerce Checkout Blocks.
 *
 * @return bool True if compatible, false otherwise.
 */
function wc_openpix_check_compatibility_checkout_block() {
    $plugin_id = wc_get_container()
        ->get( \Automattic\WooCommerce\Utilities\PluginUtil::class )
        ->get_wp_plugin_id( __FILE__ );

    return is_plugin_active( $plugin_id ) &&
        class_exists( 'Automattic\WooCommerce\Blocks\Package' ) &&
        file_exists(
            __DIR__ .
                '/assets/' .
                WC_OpenPix_Pix_Block::PIX_BLOCK_SCRIPT_FILENAME
        );
}

/**
 * Display user notice for checkout block incompatibility.
 *
 * @return false|void
 */
function wc_openpix_user_notice_incompatibility_with_checkout_block() {
    if ( wc_openpix_check_compatibility_checkout_block() ) {
        return false;
    }
}

/**
 * Display admin notice for checkout block incompatibility.
 *
 * @return false|void
 */
function wc_openpix_admin_notice_incompatibility_with_block() {
    if ( wc_openpix_check_compatibility_checkout_block() ) {
        return false;
    }

    ?>
    <div class="notice notice-warning">
        <p><strong>OpenPix</strong> <?php esc_html_e( 'is not yet compatible with WooCommerce Checkout Blocks. To avoid issues, please use the Classic Checkout.', 'openpix-for-woocommerce' ); ?></p>
    </div>
    <?php
}
