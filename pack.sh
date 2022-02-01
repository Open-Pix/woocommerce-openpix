#!/usr/bin/env bash
# remove backup files
rm **/*.po~ **/*.pot~
yarn build:prod
zip -r woocommerce-openpix-$(date "+%Y-%m-%d:%H:%M").zip \
  assets/images \
  assets/thankyou.css \
  assets/js/woo-openpix.js \
  assets/js/woo-openpix.js.LICENSE.txt \
  assets/js/woo-openpix-dev.js \
  assets/js/woo-openpix-dev.js.LICENSE.txt \
  includes/class-wc-openpix-pix.php \
   includes/class-cashback-coupon.php \
   languages \
   templates \
   woocommerce-openpix.php \
   readme.txt \
   LICENSE.txt