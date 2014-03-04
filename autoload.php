<?php

/**
 * Auto-load Dictator classes
 */
function dictator_autoloader( $class ) {

	if ( $class[0] === '\\') {
		$class = substr( $class, 1 );
	}

	if ( 0 !== strpos( $class, 'Dictator' ) ) {
		return;
	}

	// Turn Dictator\States\State into ./php/states/class-state.php
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

function meta_yaml_autoloader( $class ) {

	if ( $class[0] === '\\') {
		$class = substr( $class, 1 );
	}

	if ( 0 !== strpos( $class, 'RomaricDrigon' ) ) {
		return;
	}

	$file_parts = explode( '\\', $class );
	$file_name = array_pop( $file_parts );
	$file_name = $file_name . '.php';

	$file_path = dirname( __FILE__ ) . '/lib/MetaYaml/src/' . implode( '/', $file_parts ) . '/' . $file_name;
	if ( is_file( $file_path ) ) {
		require $file_path;
	}

}
spl_autoload_register( 'meta_yaml_autoloader' );
