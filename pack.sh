#!/usr/bin/env bash
# remove backup files
rm **/*.po~ **/*.pot~
yarn build:prod
zip -r woocommerce-openpix-$(date "+%Y-%m-%d:%H:%M").zip \
  assets \
  includes/class-wc-openpix-pix.php \
   includes/class-cashback-coupon.php \
   languages \
   templates \
   woocommerce-openpix.php \
   readme.txt \
   LICENSE.txt