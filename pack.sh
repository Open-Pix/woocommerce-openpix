#!/usr/bin/env bash
# remove backup files
rm **/*.po~ **/*.pot~
cp includes/config/config-$1.php includes/config/config.php

if [ $1 != "prod" ]
  then
    zip -r woocommerce-openpix-$1-$(date "+%Y-%m-%d:%H:%M").zip \
    includes/class-wc-openpix-pix.php \
    includes/class-wc-openpix-prod.php \
    includes/class-giftback-coupon.php \
    includes/config/config.php \
    includes/config/config-prod-beta.php \
    languages \
    templates \
    woocommerce-openpix.php \
    readme.txt \
    LICENSE.txt
  else 
      zip -r woocommerce-openpix-$1-$(date "+%Y-%m-%d:%H:%M").zip \
      includes/class-wc-openpix-pix.php \
      includes/class-giftback-coupon.php \
      includes/config/config.php \
      languages \
      templates \
      woocommerce-openpix.php \
      readme.txt \
      LICENSE.txt
  fi