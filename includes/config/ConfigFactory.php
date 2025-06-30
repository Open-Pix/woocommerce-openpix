<?php

class ConfigFactory
{
    public static function createStrategy(string $environment = EnvironmentEnum::PRODUCTION): ConfigStrategyInterface
    {
        switch ($environment) {
            case EnvironmentEnum::SANDBOX_PRODUCTION:
                return new SandboxProdConfigStrategy();
            case EnvironmentEnum::PRODUCTION:
                return new ProductionConfigStrategy();
            default:
                return new ProductionConfigStrategy();
        }
    }
} 