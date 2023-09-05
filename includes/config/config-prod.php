<?php

class OpenPixConfig
{
    public static $OPENPIX_ENV = 'production';
    public static $OPENPIX_API_URL = 'https://api.openpix.com.br';
    public static $OPENPIX_PLUGIN_URL = 'https://plugin.openpix.com.br/v1/openpix.js';
    public static $OPENPIX_PUBLIC_KEY_BASE64 = 'LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlHZk1BMEdDU3FHU0liM0RRRUJBUVVBQTRHTkFEQ0JpUUtCZ1FDLytOdElranpldnZxRCtJM01NdjNiTFhEdApwdnhCalk0QnNSclNkY2EzcnRBd01jUllZdnhTbmQ3amFnVkxwY3RNaU94UU84aWVVQ0tMU1dIcHNNQWpPL3paCldNS2Jxb0c4TU5waS91M2ZwNnp6MG1jSENPU3FZc1BVVUcxOWJ1VzhiaXM1WloySVpnQk9iV1NwVHZKMGNuajYKSEtCQUE4MkpsbitsR3dTMU13SURBUUFCCi0tLS0tRU5EIFBVQkxJQyBLRVktLS0tLQo=';
    public static $OPENPIX_PLATFORM_URL = 'https://openpix.com.br';
    
    public static function getApiUrl(): string
    {
        return OpenPixConfig::$OPENPIX_API_URL;
    }

    public static function getPluginUrl(): string
    {
        return OpenPixConfig::$OPENPIX_PLUGIN_URL;
    }

    public static function getEnv(): string
    {
        return OpenPixConfig::$OPENPIX_ENV;
    }

    public static function getWebhookUrl($gatewayClass = 'WC_OpenPix_Pix_Gateway'): string
    {
        return home_url('/') . 'wc-api/' . $gatewayClass;
    }

    public static function getPlatformUrl(): string
    {
        return OpenPixConfig::$OPENPIX_PLATFORM_URL;
    }
}

?>
