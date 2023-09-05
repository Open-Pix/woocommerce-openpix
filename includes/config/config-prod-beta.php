<?php

// @woovi/do-not-merge

class OpenPixConfig_Prod
{
    public static $OPENPIX_ENV = 'production';
    public static $OPENPIX_API_URL = 'https://api.openpix.com.br';
    public static $OPENPIX_PLUGIN_URL = 'https://plugin.openpix.com.br/v1/openpix.js';
    public static $OPENPIX_PUBLIC_KEY_BASE64 = 'LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlHZk1BMEdDU3FHU0liM0RRRUJBUVVBQTRHTkFEQ0JpUUtCZ1FEVDdWYStxb3pvT2NYUStjSHJWNk85RVE0TgpnZVhvY1ZwRFBBWkpTZVJsbEVlQVVha051MURqY3FweDFmb1l5aEZxRTM3TkNWYzRtK0hvTC9nN1k3VDMyZVJ4CjhpandxMjdoY0ZjL0RFc01ISWdVU0U4cGdPbi96a3ZadXdNb256MkVjdy85NzZzTlUzNnpKOXhMUE53dURnSysKb2dUb0RQTmNkaWtRdi9STHFRSURBUUFCCi0tLS0tRU5EIFBVQkxJQyBLRVktLS0tLQo=';
    public static $OPENPIX_PLATFORM_URL = 'https://app.openpix.com.br';

    public static function getApiUrl(): string
    {
        return OpenPixConfig_Prod::$OPENPIX_API_URL;
    }

    public static function getPluginUrl(): string
    {
        return OpenPixConfig_Prod::$OPENPIX_PLUGIN_URL;
    }

    public static function getEnv(): string
    {
        return OpenPixConfig_Prod::$OPENPIX_ENV;
    }

    public static function getWebhookUrl(
        $gatewayClass = 'WC_OpenPix_Pix_Gateway'
    ): string {
        return home_url('/') . 'wc-api/' . $gatewayClass;
    }

    public static function getPlatformUrl(): string
    {
        return OpenPixConfig_Prod::$OPENPIX_PLATFORM_URL;
    }
}

?>
