<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class WC_OpenPix_Boleto_Block extends AbstractPaymentMethodType
{
    const BOLETO_BLOCK_SCRIPT_FILENAME = 'boleto-block.js';
    private $gateway;
    protected $name = 'woocommerce_openpix_boleto';

    public function initialize()
    {
        $this->gateway = WC_OpenPix_Boleto_Gateway::instance();
        $this->settings = get_option(
            'woocommerce_woocommerce_openpix_boleto_settings',
            []
        );
    }

    public function is_active()
    {
        return $this->get_setting('enabled') === 'yes';
    }

    public function get_payment_method_script_handles()
    {
        $filenameFullDir =
            __DIR__ . '/../assets/' . self::BOLETO_BLOCK_SCRIPT_FILENAME;

        if (file_exists($filenameFullDir)) {
            // enable block integration in gutenberg
            wp_register_script(
                'wc-openpix-boleto-blocks-integration',
                plugin_dir_url(__DIR__) .
                    '/assets/' .
                    self::BOLETO_BLOCK_SCRIPT_FILENAME,
                [
                    'wc-blocks-registry',
                    'wc-settings',
                    'wp-element',
                    'wp-html-entities',
                    'wp-i18n',
                ],
                false,
                true
            );
        }

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('wc-openpix-boleto-blocks-integration');
        }

        return ['wc-openpix-boleto-blocks-integration'];
    }

    public function get_payment_method_data()
    {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'supports' => $this->get_supported_features(),
        ];
    }
}
