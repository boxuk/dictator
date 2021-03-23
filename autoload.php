<?php

/**
 * Auto-load Dictator classes.
 *
 * @param string $class Class to autoload.
 */
function dictator_autoloader( $class ) {

	if ( $class[0] === '\\' ) {
		$class = substr( $class, 1 );
	}

	if ( 0 !== strpos( $class, 'Dictator' ) ) {
		return;
	}

	// Turn Dictator\States\State into ./php/states/class-state.php.
	$file_parts = explode( '\\', str_replace( '_', '-', strtolower( $class ) ) );
	array_shift( $file_parts );
	$file_name = array_pop( $file_parts );
	$file_name = 'class-' . $file_name . '.php';

	$file_path = dirname( __FILE__ ) . '/php/' . implode( '/', $file_parts ) . '/' . $file_name;
	if ( is_file( $file_path ) ) {
		require $file_path;
	}

}
spl_autoload_register( 'dictator_autoloader' );

