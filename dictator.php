<?php
/**
 * Dictator controls the State of WordPress with WP-CLI
 *
 * Use wisely.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

if ( ! defined( 'DICTATOR' ) ) {
	define( 'DICTATOR', true );
}

// autoloader.
if (! class_exists(\BoxUk\Dictator\Dictator::class)) {
    require __DIR__ . '/vendor/autoload.php';
}

/**
 * Some files need to be manually loaded
 */
//require_once dirname( __FILE__ ) . '/autoload.php';
//require_once dirname( __FILE__ ) . '/php/class-dictator.php';
//require_once dirname( __FILE__ ) . '/php/class-dictator-translator.php';
//require_once dirname( __FILE__ ) . '/php/class-dictator-cli-command.php';
//
//Dictator::add_state( 'network', '\Dictator\States\Network' );
//Dictator::add_state( 'site', '\Dictator\States\Site' );
