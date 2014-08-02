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
	# Avoids OpenSSL alerts on Travis by using http
	wget http://cloud.github.com/downloads/Behat/Behat/behat.phar
	chmod +x behat.phar

}

install_db() {
	mysql -e 'CREATE DATABASE IF NOT EXISTS wp_cli_test;' -uroot
	mysql -e 'GRANT ALL PRIVILEGES ON wp_cli_test.* TO "wp_cli_test"@"localhost" IDENTIFIED BY "password1"' -uroot
}

install_wp_cli
download_behat
install_db
