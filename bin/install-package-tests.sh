#!/usr/bin/env bash

set -ex

install_wp_cli() {

	# the Behat test suite will pick up the executable found in $WP_CLI_BIN_DIR
	mkdir -p $WP_CLI_BIN_DIR
	wget https://github.com/wp-cli/builds/raw/gh-pages/phar/wp-cli-nightly.phar
	mv wp-cli-nightly.phar $WP_CLI_BIN_DIR/wp
	chmod +x $WP_CLI_BIN_DIR/wp

}

download_behat() {

	cd ../
	# Latest build URL causes OpenSSL issues on Travis :/
	wget https://github.com/Behat/Behat/releases/download/v3.0.12/behat.phar
	chmod +x behat.phar

}

install_db() {
	mysql -e 'CREATE DATABASE IF NOT EXISTS wp_cli_test;' -uroot
	mysql -e 'GRANT ALL PRIVILEGES ON wp_cli_test.* TO "wp_cli_test"@"localhost" IDENTIFIED BY "password1"' -uroot
}

install_wp_cli
download_behat
install_db
