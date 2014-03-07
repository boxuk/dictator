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
	public static function is_valid_state( $name ) {

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
	 * Get the object for a given state
	 * 
	 * @param string $name Name of the state
	 * @param array $yaml Data from the state file
	 * @return object|false
	 */
	public static function get_state_obj( $name, $yaml = null ) {

		if ( self::called_statically() ) {
			return Dictator::get_instance()->get_state_obj( $name, $yaml );
		}

		if ( ! isset( self::$instance->states[ $name ] ) ) {
			return false;
		}

		$class = self::$instance->states[ $name ][ 'class' ];

		return new $class( $yaml );
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

	/**
	 * Recursive difference of an array
	 * 
	 * @see https://gist.github.com/vincenzodibiaggio/5965342
	 * 
	 * @param array
	 * @param array
	 * @return array
	 */
	public static function array_diff_recursive( $array_1, $array_2 ) {

		$ret = array();
	
		foreach ( $array_1 as $key => $value ) {

			if ( array_key_exists( $key, $array_2 ) ) {

				if ( is_array( $value ) ) {

					$recursive_diff = self::array_diff_recursive( $value, $array_2[ $key ] );

					if ( count( $recursive_diff ) ) {
						$ret[ $key ] = $recursive_diff;
					}
				
				} else {
					
					if ( $value !== $array_2[ $key ] ) {
						
						$ret[ $key ] = $value;
					
					}
				
				}

			} else {

				$ret[ $key ] = $value;

			}

		}

		return $ret;

	}
	
}
