

<?php class OpenPixConfig
{
    public static $OPENPIX_ENV = 'development';
    public static $OPENPIX_API_URL = 'http://localhost:5001';
    public static $OPENPIX_PLUGIN_URL = 'http://localhost:4444/openpix.js';

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
        $webhookUrl = str_replace(
            'https:',
            'http:',
            home_url('/') . 'wc-api/' . 'WC_OpenPix_Pix_Gateway'
        );

        return $webhookUrl;
    }
}

?>
