<?php

/**
 * Plugin Name: OpenPix for WooCommerce
 * Description: Aceite pagamentos Pix em com atualização em tempo real, checkout transparente e atualização automática de status do pedido.
 * Author: OpenPix
 * Author URI: https://openpix.com.br/
 * Version: 2.13.2
 * Text Domain: woocommerce-openpix
 * WC tested up to: 8.2.2
 * Requires Plugins: woocommerce
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
    const VERSION = '2.13.2';

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
            'userNoticeIncompatibilityWithCheckoutBlock'
        );
        add_action('admin_notices', 'adminNoticeIncompatibilityWithBlock');
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
            __('Settings Pix', 'woocommerce-openpix') .
            '</a>';

        $plugin_links[] =
            '<a href="' .
            esc_url(
                admin_url(
                    'admin.php?page=wc-settings&tab=checkout&section=woocommerce_openpix_pix_parcelado'
                )
            ) .
            '">' .
            __('Settings Parcelado', 'woocommerce-openpix') .
            '</a>';

        $plugin_links[] =
            '<a href="' .
            esc_url(
                admin_url(
                    'admin.php?page=wc-settings&tab=checkout&section=woocommerce_openpix_pix_crediary'
                )
            ) .
            '">' .
            __('Settings Pix Crediary', 'woocommerce-openpix') .
            '</a>';

        $plugin_links[] =
            '<a  target="_blank" href="https://developers.openpix.com.br/docs/ecommerce/woocommerce/woocommerce-plugin">' .
            __('Documentation', 'woocommerce-openpix') .
            '</a>';

        $plugin_links[] =
            '<a  target="_blank" href="https://app.openpix.com/register/?src=wordpress"> ' .
            __('Sign up', 'woocommerce-openpix') .
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

        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function (
                Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry
            ) {
                $payment_method_registry->register(new WC_OpenPix_Pix_Block());
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

        $jsonEncodedObject = json_encode(
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

function checkCompabilityCheckoutBlock()
{
    $pluginId = wc_get_container()
        ->get(\Automattic\WooCommerce\Utilities\PluginUtil::class)
        ->get_wp_plugin_id(__FILE__);

    return is_plugin_active($pluginId) &&
        class_exists('Automattic\WooCommerce\Blocks\Package') &&
        file_exists(
            __DIR__ .  '/assets/' . WC_OpenPix_Pix_Block::PIX_BLOCK_SCRIPT_FILENAME
        );
}

function userNoticeIncompatibilityWithCheckoutBlock()
{
    if (checkCompabilityCheckoutBlock()) {
        return false;
    }

    //     echo <<<HTML
    //     <div clas    s="woocommerce-info">
    //         Atenção: O plugin "woocommerce-openpix" ainda não oferece suporte ao Checkout Blocks. Então estamos usando o modelo de checkout classico.
    //     </div>
    // HTML;
}

function adminNoticeIncompatibilityWithBlock()
{
    if (checkCompabilityCheckoutBlock()) {
        return false;
    }

    echo <<<HTML
    <div class="notice notice-warning">
        <p><strong>woocommerce-openpix</strong> ainda não é compatível com o Checkout de Blocos do WooCommerce. Para evitar problemas, utilize o Checkout Classico.</p>
    </div>
HTML;
}
