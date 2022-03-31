<?php

class OpenPixConfig_Prod
{
    public static $OPENPIX_ENV = 'production';
    public static $OPENPIX_API_URL = 'https://api.openpix.com.br';
    public static $OPENPIX_PLUGIN_URL = 'https://plugin.openpix.com.br/v1/openpix.js';

    public static function getApiUrl()
    {
        return OpenPixConfig_Prod::$OPENPIX_API_URL;
    }

    public static function getPluginUrl()
    {
        return OpenPixConfig_Prod::$OPENPIX_PLUGIN_URL;
    }

    public static function getEnv()
    {
        return OpenPixConfig_Prod::$OPENPIX_ENV;
    }

    public static function getWebhookUrl()
    {
        return home_url('/') . 'wc-api/' . 'WC_OpenPix_Pix_Gateway';
    }
}

?>
