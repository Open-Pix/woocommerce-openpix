#!/usr/bin/env bash
# remove backup files
rm **/*.po~ **/*.pot~
yarn build:prod
zip -r woocommerce-openpix.zip assets includes/class-wc-openpix-pix.php languages templates woocommerce-openpix.php readme.txt LICENSE.txt