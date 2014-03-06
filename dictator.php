<?php
/**
 * The Dictator controls the State of WordPress with WP-CLI
 * 
 * Use wisely.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

define( 'DICTATOR', true );

/**
 * Some files need to be manually loaded
 */
require_once dirname( __FILE__ ) . '/autoload.php';
// array_column was introduced in PHP 5.5
if ( ! function_exists( 'array_column' ) ) {
	require_once dirname( __FILE__ ) . '/lib/ramsey/array_column/src/array_column.php';
}
require_once dirname( __FILE__ ) . '/php/class-dictator.php';
require_once dirname( __FILE__ ) . '/php/class-dictator-cli-command.php';

Dictator::add_state( 'network', '\Dictator\States\Network', 'network.yml' );