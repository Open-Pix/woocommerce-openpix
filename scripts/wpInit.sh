#!/usr/bin/env bash

export DISABLE_AUTO_TITLE="true"
echo -n -e "\033]0;OpenPixEcommerce Shell\007"

export MYSQL_HOME="/Users/sibelius/Library/Application Support/Local/run/Pw1bnuc4h/conf/mysql"
export PHPRC="/Users/sibelius/Library/Application Support/Local/run/Pw1bnuc4h/conf/php"
export WP_CLI_CONFIG_PATH="/Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/config.yaml"
export WP_CLI_DISABLE_AUTO_CHECK_UPDATE=1

# Add PHP, MySQL, and WP-CLI to $PATH
echo "Setting Local environment variables..."

export PATH="/Applications/Local.app/Contents/Resources/extraResources/lightning-services/mysql-8.0.16+5/bin/darwin/bin:$PATH"
export PATH="/Applications/Local.app/Contents/Resources/extraResources/lightning-services/php-7.3.5+10/bin/darwin/bin:$PATH"
export PATH="/Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/posix:$PATH"
export PATH="/Applications/Local.app/Contents/Resources/extraResources/bin/composer/posix:$PATH"



echo "----"
echo "WP-CLI:   $(wp --version)"
echo "Composer: $(composer --version | cut -f3-4 -d" ")"
echo "PHP:      $(php -r "echo PHP_VERSION;")"
echo "MySQL:    $(mysql --version)"
echo "----"