#!/usr/bin/env bash
# remove backup files
rm **/*.po~ **/*.pot~
# cp includes/config/config-$1.php includes/config/config.php

# Get plugin version
VERSION=$(grep -oP 'Version: \K[0-9.]+' woocommerce-openpix.php)

# Install Composer dependencies (optimized for production)
composer install --no-dev --optimize-autoloader

if [ $1 != "prod" ]
  then
    zip -r woocommerce-openpix-$1-v$VERSION-$(date "+%Y-%m-%d:%H:%M").zip \
    assets/pix-block.js \
    assets/thankyou.css \
    includes/class-wc-openpix-pix.php \
    includes/class-wc-openpix-pix-parcelado.php \
    includes/class-wc-openpix-pix-crediary.php \
    includes/customer/class-wc-openpix-customer.php \
    includes/class-wc-openpix-prod.php \
    includes/class-wc-openpix-pix-block.php \
    includes/config/config.php \
    includes/config/autoload.php \
    includes/config/EnvironmentEnum.php \
    includes/config/ConfigFactory.php \
    includes/config/ConfigStrategyInterface.php \
    includes/config/ProductionConfigStrategy.php \
    includes/config/SandboxProdConfigStrategy.php \
    languages \
    templates \
    vendor \
    woocommerce-openpix.php \
    readme.txt \
    LICENSE.txt
  else
      zip -r woocommerce-openpix-$1-v$VERSION-$(date "+%Y-%m-%d:%H:%M").zip \
      assets/pix-block.js \
      assets/thankyou.css \
      includes/class-wc-openpix-pix.php \
      includes/class-wc-openpix-pix-parcelado.php \
      includes/class-wc-openpix-pix-crediary.php \
      includes/class-wc-openpix-pix-block.php \
      includes/customer/class-wc-openpix-customer.php \
      includes/config/config.php \
      includes/config/autoload.php \
      includes/config/EnvironmentEnum.php \
      includes/config/ConfigFactory.php \
      includes/config/ConfigStrategyInterface.php \
      includes/config/ProductionConfigStrategy.php \
      includes/config/SandboxProdConfigStrategy.php \
      languages \
      templates \
      vendor \
      woocommerce-openpix.php \
      readme.txt \
      LICENSE.txt
  fi
