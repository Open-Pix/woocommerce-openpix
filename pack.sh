#!/usr/bin/env bash
# remove backup files
rm **/*.po~ **/*.pot~
cp includes/config/config-$1.php includes/config/config.php
yarn build:$1
zip -r woocommerce-openpix-$1-$(date "+%Y-%m-%d:%H:%M").zip \
  assets/images \
  assets/thankyou.css \
  assets/js/woo-openpix.js \
  assets/js/woo-openpix.js.LICENSE.txt \
  assets/js/woo-openpix-dev.js \
  assets/js/woo-openpix-dev.js.LICENSE.txt \
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