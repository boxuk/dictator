<?php

use RomaricDrigon\MetaYaml\MetaYaml;
use RomaricDrigon\MetaYaml\Exception\NodeValidatorException;

/**
 * The Dictator controls the State of WordPress.
 */
class Dictator_CLI_Command extends WP_CLI_Command {

	/**
	 * Export a state of WordPress
	 * 
	 * ## OPTIONS
	 * 
	 * <state>
	 * : State to export
	 * 
	 * <file>
	 * : Where the state should be exported to
	 * 
	 * @subcommand export
	 */
	public function export( $args, $assoc_args ) {

		list( $state, $file ) = $args;


	}

	/**
	 * Impose a state onto WordPress
	 * 
	 * ## OPTIONS
	 * 
	 * <file>
	 * : State file to impose
	 *
	 * @subcommand impose
	 */
	public function impose( $args, $assoc_args ) {

		list( $file ) = $args;

		$yaml = $this->load_state_file( $file );

		Dictator::get_state_schema( $yaml['state'] );

		error_log( var_export( $yaml, true  ) );

	}

	/**
	 * Validate a state file
	 * 
	 * ## OPTIONS
	 * 
	 * <file>
	 * : State file to load
	 *
	 * @subcommand validate
	 */
	public function validate( $args, $assoc_args ) {

		list( $file ) = $args;

		$yaml = $this->load_state_file( $file );

		$this->validate_state_data( $yaml );

		WP_CLI::success( "State validates against the schema." );

	}

	/**
	 * Load a given Yaml state file
	 *
	 * @param string $file
	 * @return object
	 */
	private function load_state_file( $file ) {

		if ( ! file_exists( $file ) ) {
			WP_CLI::error( sprintf( "File doesn't exist: %s", $file ) );
		}

		$yaml = spyc_load( file_get_contents( $file ) );
		if ( empty( $yaml ) ) {
			WP_CLI::error( sprintf( "Doesn't appear to be a Yaml file: %s", $file ) );
		}

		return $yaml;
	}

	/**
	 * Validate the provided state file
	 *
	 * @param array $yaml Data from the state file
	 */
	private function validate_state_data( $yaml ) {

		if ( empty( $yaml[ 'state' ] )
			|| ! Dictator::is_valid_state( $yaml[ 'state' ] ) ) {
			WP_CLI::error( "Incorrect state." );
		}	

		$schema = Dictator::get_state_schema_obj( $yaml['state'] );

		try {
			$schema->validate( $yaml );
		} catch ( NodeValidatorException $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		return true;

	}

}

WP_CLI::add_command( 'dictator', 'Dictator_CLI_Command' );