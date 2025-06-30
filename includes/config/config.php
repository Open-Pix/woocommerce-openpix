<?php

class OpenPixConfig
{
    private static ?ConfigStrategyInterface $strategy = null;
    private static string $environment = EnvironmentEnum::PRODUCTION;

    public static function initialize(string $environment = EnvironmentEnum::PRODUCTION): void
    {
        self::$environment = $environment;
        self::$strategy = ConfigFactory::createStrategy($environment);
    }

    private static function getStrategy(): ConfigStrategyInterface
    {
        if (self::$strategy === null) {
            self::initialize();
        }
        return self::$strategy;
    }

    public static function getApiUrl(): string
    {
        return self::getStrategy()->getApiUrl();
    }

    public static function getPluginUrl(): string
    {
        return self::getStrategy()->getPluginUrl();
    }

    public static function getEnv(): string
    {
        return self::getStrategy()->getEnv();
    }

    public static function getWebhookUrl(
        $gatewayClass = 'WC_OpenPix_Pix_Gateway'
    ): string {
        return self::getStrategy()->getWebhookUrl($gatewayClass);
    }

    public static function getPlatformUrl(): string
    {
        return self::getStrategy()->getPlatformUrl();
    }

    public static function getPublicKeyBase64(): string
    {
        return self::getStrategy()->getPublicKeyBase64();
    }
}

?>