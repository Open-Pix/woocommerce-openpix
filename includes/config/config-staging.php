

<?php class OpenPixConfig
{
    public static $OPENPIX_ENV = 'staging';
    public static $OPENPIX_API_URL = 'https://api.openpix.dev';
    public static $OPENPIX_PLUGIN_URL = 'https://plugin.openpix.dev/v1/openpix-dev.js';

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

    public static function getWebhookUrl()
    {
        return home_url('/') . 'wc-api/' . 'WC_OpenPix_Pix_Gateway';
    }
}

?>
