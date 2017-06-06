#!/usr/bin/env bash

set -ex

PACKAGE_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )"/../ && pwd )"

set_package_context() {

	touch $WP_CLI_CONFIG_PATH
	printf 'require:' > $WP_CLI_CONFIG_PATH
	requires=$(php $PACKAGE_DIR/utils/get-package-require-from-composer.php composer.json)
	for require in "${requires[@]}"
	do
		printf "\n%2s-%1s$PACKAGE_DIR/$require" >> $WP_CLI_CONFIG_PATH
	done
	printf "\n" >> $WP_CLI_CONFIG_PATH

}

download_behat() {

	cd $PACKAGE_DIR
	curl -s https://getcomposer.org/installer | php
	php composer.phar require --dev behat/behat='~2.5'

}

install_db() {
	mysql -e 'CREATE DATABASE IF NOT EXISTS wp_cli_test;' -uroot
	mysql -e 'GRANT ALL PRIVILEGES ON wp_cli_test.* TO "wp_cli_test"@"localhost" IDENTIFIED BY "password1"' -uroot
}

set_package_context
download_behat
install_db
