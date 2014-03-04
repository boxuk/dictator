<?php

use \RomaricDrigon\MetaYaml\MetaYaml;

class Dictator {

	private static $instance;

	private $states = array();

	/**
	 * Get the instance of the dictator
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new Dictator;
		}
		return self::$instance;

	}

	/**
	 * Whether or not this was called statically
	 * 
	 * @return bool
	 */
	private static function called_statically() {

		if ( isset( self::$instance ) && get_class( self::$instance ) == __CLASS__ ) {
			return false;
		} else {
			return true;
		}

	}

	/**
	 * Register a state that the Dictator can control
	 * 
	 * @param string $name Name of the state
	 * @param string $class Class that represents state's relationship with WP
	 * @param string $schema Schema file
	 */
	public static function add_state( $name, $class, $schema ) {

		if ( self::called_statically() ) {
			return Dictator::get_instance()->add_state( $name, $class, $schema );
		}

		// @todo validate the class is callable and the schema exists

		$state = array(
			'class'      => $class,
			'schema'     => $schema,
			);

		self::$instance->states[ $name ] = $state;
	}

	/**
	 * Whether or not the state is valid
	 * 
	 * @param string $name Name of the state
	 * @return bool
	 */
	public static function is_valid_state( $state ) {

		if ( self::called_statically() ) {
			return Dictator::get_instance()->is_valid_state( $name );
		}

		if ( isset( self::$instance->states[ $name ] ) ) {
			return true;
		} else {
			return false;
		}

	}


	/**
	 * Get the schema object for a state
	 * 
	 * @param string $name Name of the state
	 * @return object|false 
	 */
	public static function get_state_schema_obj( $name ) {

		if ( self::called_statically() ) {
			return Dictator::get_instance()->get_state_schema( $name );
		}

		if ( ! isset( self::$instance->states[ $name ] ) ) {
			return false;
		}

		$state = self::$instance->states[ $name ];

		$schema_file = $state[ 'schema' ];
		if ( ! file_exists( $schema_file ) ) {
			$schema_file = dirname( dirname( __FILE__ ) ) . '/schemas/' . $schema_file;
		}

		$schema_yaml = spyc_load( file_get_contents( $schema_file ) );

		return new MetaYaml( $schema_yaml );

	}

	
}