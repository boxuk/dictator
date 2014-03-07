<?php

use RomaricDrigon\MetaYaml\MetaYaml;
use RomaricDrigon\MetaYaml\Exception\NodeValidatorException;

/**
 * The Dictator controls the State of WordPress.
 */
class Dictator_CLI_Command extends WP_CLI_Command {

	private $output_nesting_level = 0;

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
	 * [--force]
	 * : Forcefully overwrite an existing state file if one exists.
	 * 
	 * @subcommand export
	 */
	public function export( $args, $assoc_args ) {

		list( $state, $file ) = $args;

		// @todo throw a warning if a state file is already detected here

		$state_obj = Dictator::get_state_obj( $state );
		if ( ! $state_obj ) {
			WP_CLI::error( "Invalid state supplied." );
		}

		// Build the state's data
		$state_data = array( 'state' => $state );
		foreach( $state_obj->get_regions() as $region_name => $region_obj ) {
			$state_data[ $region_name ] = $region_obj->get_current_data();
		}

		$this->write_state_file( $state_data, $file );

		WP_CLI::success( "State written to file." );
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

		$this->validate_state_data( $yaml );

		$state_obj = Dictator::get_state_obj( $yaml['state'], $yaml );

		foreach( $state_obj->get_regions() as $region_obj ) {

			if ( $region_obj->is_under_accord() ) {
				continue;
			}

			WP_CLI::line( sprintf( '%s:', $state_obj->get_region_name( $region_obj ) ) );

			// Render the differences for the region
			$differences = $region_obj->get_differences();
			foreach( $differences as $slug => $difference ) {
				$this->show_difference( $slug, $difference );

				$to_impose = \Dictator::array_diff_recursive( $difference['dictated'], $difference['current'] );
				$ret = $region_obj->impose( $slug, $difference['dictated'] );
				if ( is_wp_error( $ret ) ) {
					WP_CLI::warning( $ret->get_error_message() );
				}
			}

		}

		WP_CLI::success( "The Dictator has imposed upon the State of WordPress." );

	}

	/**
	 * Compare the State of WordPress to a state file
	 *
	 * ## OPTIONS
	 * 
	 * <file>
	 * : State file to compare
	 * 
	 * @subcommand compare
	 * @alias diff
	 */
	public function compare( $args, $assoc_args ) {

		list( $file ) = $args;

		$yaml = $this->load_state_file( $file );

		$this->validate_state_data( $yaml );

		$state_obj = Dictator::get_state_obj( $yaml['state'], $yaml );

		foreach( $state_obj->get_regions() as $region_name => $region_obj ) {

			if ( $region_obj->is_under_accord() ) {
				continue;
			}

			WP_CLI::line( sprintf( '%s:', $region_name ) );

			// Render the differences for the region
			$differences = $region_obj->get_differences();
			foreach( $differences as $slug => $difference ) {
				$this->show_difference( $slug, $difference );
			}

		}

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

	/**
	 * Write a state object to a file
	 * 
	 * @param object $state_obj
	 * @param string $file
	 */
	private function write_state_file( $state_data, $file ) {

		$spyc = new Spyc;
		$file_data = $spyc->dump( $state_data );
		file_put_contents( $file, $file_data );
	}

	/**
	 * Visually depict the difference between "dictated" and "current"
	 * 
	 * @param array
	 */
	private function show_difference( $slug, $difference ) {

		$this->output_nesting_level = 0;

		// Data already exists within WordPress
		if ( ! empty( $difference['current'] ) ) {

			$this->nested_line( $slug . ': ' );

			$this->recursively_show_difference( $difference['dictated'], $difference['current'] );

		} else {

			$this->add_line( $slug . ': ' );

			$this->recursively_show_difference( $difference['dictated'] );

		}

		$this->output_nesting_level = 0;

	}

	/**
	 * Recursively output the difference between "dictated" and "current"
	 */
	private function recursively_show_difference( $dictated, $current = null ) {

		$this->output_nesting_level++;

		if ( $this->is_assoc_array( $dictated ) ) {

			foreach( $dictated as $key => $value ) {

				if ( $this->is_assoc_array( $value ) || is_array( $value ) ) {

					$new_current = isset( $current[ $key ] ) ? $current[ $key ] : null;
					if ( $new_current ) {
						$this->nested_line( $key . ': ' );
					} else {
						$this->add_line( $key . ': ' );
					}

					$this->recursively_show_difference( $value, $new_current );

				} else if ( is_string( $value ) ) {

					$pre = $key . ': ';

					if ( isset( $current[ $key ] ) && $current[ $key ] !== $value ) {

						$this->remove_line( $pre . $current[ $key ] );
						$this->add_line( $pre . $value );

					} else if ( ! isset( $current[ $key ] ) ) {

						$this->add_line( $pre . $value );

					}

				}

			}

		} else if ( is_array( $dictated ) ) {

			foreach( $dictated as $value ) {

				if ( ! $current 
					|| ! in_array( $value, $current ) ) {
					$this->add_line( '- ' . $value );
				}

			}

		} else if ( is_string( $value ) ) {

			$pre = $key . ': ';

			if ( isset( $current[ $key ] ) && $current[ $key ] !== $value ) {

				$this->remove_line( $pre . $current[ $key ] );
				$this->add_line( $pre . $value );

			} else if ( ! isset( $current[ $key ] ) ) {

				$this->add_line( $pre . $value );

			} else {

				$this->nested_line( $pre );

			}

		}

		$this->output_nesting_level--;

	}

	/**
	 * Output a line to be added
	 * 
	 * @param string
	 */
	private function add_line( $line ) {
		$this->nested_line( $line, 'add' );
	}

	/**
	 * Output a line to be removed
	 * 
	 * @param string
	 */
	private function remove_line( $line ) {
		$this->nested_line( $line, 'remove' );
	}

	/**
	 * Output a line that's appropriately nested
	 */
	private function nested_line( $line, $change = false ) {

		if ( 'add' == $change ) {
			$color = '%G';
			$label = '+ ';
		} else if ( 'remove' == $change ) {
			$color = '%R';
			$label = '- ';
		} else {
			$color = false;
			$label = false;
		}

		\cli\Colors::colorize( "%n" );

		$spaces = ( $this->output_nesting_level * 2 ) + 2;
		if ( $color && $label ) {
			$line = \cli\Colors::colorize( "{$color}{$label}{$line}%n" );
			$spaces = $spaces - 2;
		}
		WP_CLI::line( str_pad( ' ', $spaces ) . $line );
	}

	/**
	 * Whether or not this is an associative array
	 * 
	 * @param array
	 * @return bool
	 */
	private function is_assoc_array( $array ) {

		if ( ! is_array( $array ) ) {
			return false;
		}

		return array_keys( $array ) !== range( 0, count( $array ) - 1 );
	}


}

WP_CLI::add_command( 'dictator', 'Dictator_CLI_Command' );