<?php

class ProductionConfigStrategy implements ConfigStrategyInterface
{
    private const OPENPIX_ENV = 'production';
    private const OPENPIX_API_URL = 'https://api.openpix.com.br';
    private const OPENPIX_PLUGIN_URL = 'https://plugin.openpix.com.br/v1/openpix.js';
    private const OPENPIX_PUBLIC_KEY_BASE64 = 'LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlHZk1BMEdDU3FHU0liM0RRRUJBUVVBQTRHTkFEQ0JpUUtCZ1FDLytOdElranpldnZxRCtJM01NdjNiTFhEdApwdnhCalk0QnNSclNkY2EzcnRBd01jUllZdnhTbmQ3amFnVkxwY3RNaU94UU84aWVVQ0tMU1dIcHNNQWpPL3paCldNS2Jxb0c4TU5waS91M2ZwNnp6MG1jSENPU3FZc1BVVUcxOWJ1VzhiaXM1WloySVpnQk9iV1NwVHZKMGNuajYKSEtCQUE4MkpsbitsR3dTMU13SURBUUFCCi0tLS0tRU5EIFBVQkxJQyBLRVktLS0tLQo=';
    private const OPENPIX_PLATFORM_URL = 'https://app.openpix.com.br';

    public function getEnv(): string
    {
        return self::OPENPIX_ENV;
    }

    public function getApiUrl(): string
    {
        return self::OPENPIX_API_URL;
    }

    public function getPluginUrl(): string
    {
        return self::OPENPIX_PLUGIN_URL;
    }

    public function getPublicKeyBase64(): string
    {
        return self::OPENPIX_PUBLIC_KEY_BASE64;
    }

    public function getPlatformUrl(): string
    {
        return self::OPENPIX_PLATFORM_URL;
    }

    public function getWebhookUrl(string $gatewayClass = 'WC_OpenPix_Pix_Gateway'): string
    {
        return home_url('/') . 'wc-api/' . $gatewayClass;
    }
} 