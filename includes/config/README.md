# OpenPix Configuration Strategy Pattern

This directory contains the implementation of the Strategy Pattern for the OpenPix WooCommerce plugin configuration.

## Overview

The configuration system has been refactored to use the Strategy Pattern, which allows for easy switching between different environment configurations (sandbox-prod, production) without modifying code.

## Files

### Core Strategy Files

- **`ConfigStrategyInterface.php`** - Interface defining the contract for all configuration strategies
- **`SandboxProdConfigStrategy.php`** - Strategy for sandbox-prod environment
- **`ProductionConfigStrategy.php`** - Strategy for production environment
- **`ConfigFactory.php`** - Factory class for creating the appropriate strategy
- **`config.php`** - Main configuration context class that uses the strategy pattern
- **`autoload.php`** - Autoloader that includes all necessary files

### Legacy Files (Deprecated)

- **`config-sandbox-prod.php`** - Legacy sandbox configuration (deprecated)
- **`config-prod.php`** - Legacy production configuration (deprecated)
- **`config-development.php`** - Legacy development configuration (deprecated)
- **`config-staging.php`** - Legacy staging configuration (deprecated)
- **`config-prod-beta.php`** - Legacy beta configuration (deprecated)

## Usage

### Basic Usage

The configuration is automatically initialized when the plugin loads. You can access configuration values using the static methods:

```php
// Get API URL
$apiUrl = OpenPixConfig::getApiUrl();

// Get environment
$env = OpenPixConfig::getEnv();

// Get plugin URL
$pluginUrl = OpenPixConfig::getPluginUrl();

// Get platform URL
$platformUrl = OpenPixConfig::getPlatformUrl();

// Get public key
$publicKey = OpenPixConfig::getPublicKeyBase64();

// Get webhook URL
$webhookUrl = OpenPixConfig::getWebhookUrl('WC_OpenPix_Pix_Gateway');
```

### Manual Initialization

You can manually initialize the configuration with a specific environment:

```php
// Initialize with specific environment
OpenPixConfig::initialize('sandbox-prod');
OpenPixConfig::initialize('production');

OpenPixConfig::initialize();
```

### Environment Detection

The system automatically detects the environment based on Plugin option settings:

## Adding New Environments

To add a new environment configuration:

1. Create a new strategy class implementing `ConfigStrategyInterface`
2. Add the new strategy to the `ConfigFactory::createStrategy()` method
3. Update the environment detection logic if needed

Example:

```php
class DevelopmentConfigStrategy implements ConfigStrategyInterface
{
    private const OPENPIX_ENV = 'development';
    private const OPENPIX_API_URL = 'https://api.dev.openpix.com.br';
    // ... other constants

    public function getEnv(): string
    {
        return self::OPENPIX_ENV;
    }
    
    // ... implement other methods
}
```

## Benefits

1. **Separation of Concerns**: Each environment has its own strategy class
2. **Easy Maintenance**: Adding new environments doesn't require modifying existing code
3. **Type Safety**: Interface ensures all strategies implement required methods
4. **No File Copying**: Eliminates the need to copy configuration files
5. **Runtime Switching**: Can switch environments at runtime if needed
6. **Testability**: Each strategy can be tested independently

## Migration from Legacy System

The legacy system used file copying to switch between configurations. The new strategy pattern:

- Eliminates the need for file copying
- Provides better type safety
- Makes the code more maintainable
- Allows for easier testing
- Reduces the risk of configuration errors

All existing code that used `OpenPixConfig::$OPENPIX_*` static properties has been updated to use the new method-based approach. 