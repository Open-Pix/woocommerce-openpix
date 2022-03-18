<?php

class OpenPixConfig
{
    public static $OPENPIX_ENV = 'production';
    public static $OPENPIX_API_URL = 'https://api.openpix.com.br';
    public static $OPENPIX_PLUGIN_URL = 'https://plugin.openpix.com.br/v1/openpix.js';

    public static function getApiUrl()
    {
        return OpenPixConfig::$OPENPIX_API_URL;
    }

    public static function getPluginUrl()
    {
        return OpenPixConfig::$OPENPIX_PLUGIN_URL;
    }

    public static function getEnv()
    {
        return OpenPixConfig::$OPENPIX_ENV;
    }

    public static function getCheckoutUrl()
    {
        return plugins_url(
            'assets/js/woo-openpix.js',
            plugin_dir_path(__FILE__)
        );
    }

    public static function getWebhookUrl()
    {
        return home_url('/') . 'wc-api/' . 'WC_OpenPix_Pix_Gateway';
    }
}

?>
