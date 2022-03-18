

<?php class OpenPixConfig
{
    public static $OPENPIX_ENV = 'staging';
    public static $OPENPIX_API_URL = 'https://api.openpix.dev';
    public static $OPENPIX_PLUGIN_URL = 'https://plugin.openpix.com.br/v1/openpix-dev.js';

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
}

?>
