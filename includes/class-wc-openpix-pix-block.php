<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class WC_OpenPix_Pix_Block extends AbstractPaymentMethodType {
	private $gateway;
	protected $name = 'woocommerce_openpix_pix';

	public function initialize() {
		$this->gateway = WC_OpenPix_Pix_Gateway::instance();
		$this->settings = get_option('woocommerce_woocommerce_openpix_pix_settings', []);
	}

	public function is_active() {
		return $this->get_setting('enabled') === 'yes';
	}

	public function get_payment_method_script_handles()
	{
		wp_register_script(
			'wc-openpix-pix-blocks-integration',
			plugin_dir_url(__DIR__) . 'assets/pix-block.js',
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
		
		if(function_exists('wp_set_script_translations')) {
			wp_set_script_translations('wc-openpix-pix-blocks-integration');
		}

		return ['wc-openpix-pix-blocks-integration'];
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