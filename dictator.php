<?php
/*
Plugin Name: Dictator
Version: 0.1-alpha
Description: The Dictator controls the State of WordPress
Author: danielbachhuber, humanmade
Author URI: http://hmn.md/
Plugin URI: http://wordpress.org/extend/plugins/dictator/
Text Domain: dictator
Domain Path: /languages
*/

if ( defined( 'WP_CLI' ) && WP_CLI )
	require_once dirname( __FILE__ ) . '/inc/class-dictator-cli-command.php';
