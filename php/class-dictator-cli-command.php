<?php

/**
 * Dictator controls the State of WordPress.
 */
class Dictator_CLI_Command extends WP_CLI_Command {

	/**
	 * Output nesting level.
	 *
	 * @var int $output_nesting_level
	 */
	private $output_nesting_level = 0;

	/**
	 * Export the State of WordPress to a state file.
	 *
	 * ## OPTIONS
	 *
	 * <state>
	 * : State to export
	 *
	 * <file>
	 * : Where the state should be exported to
	 *
	 * [--regions=<regions>]
	 * : Limit the export to one or more regions.
	 *
	 * [--force]
	 * : Forcefully overwrite an existing state file if one exists.
	 *
	 * @subcommand export
	 *
	 * @param array $args Args.
	 * @param array $assoc_args Assoc Args.
	 *
	 * @throws \WP_CLI\ExitException Exits on error, such as bad state supplied.
	 */
	public function export( $args, $assoc_args ) {

		list( $state, $file ) = $args;

		if ( file_exists( $file ) && ! isset( $assoc_args['force'] ) ) {
			WP_CLI::confirm( 'Are you sure you want to overwrite the existing state file?' );
		}

		$state_obj = Dictator::get_state_obj( $state );
		if ( ! $state_obj ) {
			WP_CLI::error( 'Invalid state supplied.' );
		}

		$limited_regions = ! empty( $assoc_args['regions'] ) ? explode( ',', $assoc_args['regions'] ) : array();

		// Build the state's data.
		$state_data = array( 'state' => $state );
		foreach ( $state_obj->get_regions() as $region_obj ) {

			$region_name = $state_obj->get_region_name( $region_obj );

			if ( $limited_regions && ! in_array( $region_name, $limited_regions, true ) ) {
				continue;
			}

			$state_data[ $region_name ] = $region_obj->get_current_data();
		}

		$this->write_state_file( $state_data, $file );

		WP_CLI::success( 'State written to file.' );
	}

	/**
	 * Impose a given state file onto WordPress.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : State file to impose
	 *
	 * [--regions=<regions>]
	 * : Limit the imposition to one or more regions.
	 *
	 * @subcommand impose
	 *
	 * @param array $args Args.
	 * @param array $assoc_args Assoc args.
	 */
	public function impose( $args, $assoc_args ) {

		list( $file ) = $args;

		$yaml = $this->load_state_file( $file );

		$this->validate_state_data( $yaml );

		$state_obj = Dictator::get_state_obj( $yaml['state'], $yaml );

		$limited_regions = ! empty( $assoc_args['regions'] ) ? explode( ',', $assoc_args['regions'] ) : array();

		foreach ( $state_obj->get_regions() as $region_obj ) {

			$region_name = $state_obj->get_region_name( $region_obj );

			if ( $limited_regions && ! in_array( $region_name, $limited_regions, true ) ) {
				continue;
			}

			if ( $region_obj->is_under_accord() ) {
				continue;
			}

			WP_CLI::line( sprintf( '%s:', $region_name ) );

			// Render the differences for the region.
			$differences = $region_obj->get_differences();
			foreach ( $differences as $slug => $difference ) {
				$this->show_difference( $slug, $difference );

				$to_impose = \Dictator::array_diff_recursive( $difference['dictated'], $difference['current'] );
				$ret       = $region_obj->impose( $slug, $difference['dictated'] );
				if ( is_wp_error( $ret ) ) {
					WP_CLI::warning( $ret->get_error_message() );
				}
			}
		}

		WP_CLI::success( 'The Dictator has imposed upon the State of WordPress.' );

	}

	/**
	 * Compare a given state file to the State of WordPress.
	 * Produces a colorized diff if differences, otherwise empty output.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : State file to compare
	 *
	 * @subcommand compare
	 * @alias diff
	 *
	 * @param arrau $args Args.
	 * @param array $assoc_args Assoc args.
	 */
	public function compare( $args, $assoc_args ) {

		list( $file ) = $args;

		$yaml = $this->load_state_file( $file );

		$this->validate_state_data( $yaml );

		$state_obj = Dictator::get_state_obj( $yaml['state'], $yaml );

		foreach ( $state_obj->get_regions() as $region_name => $region_obj ) {

			if ( $region_obj->is_under_accord() ) {
				continue;
			}

			WP_CLI::line( sprintf( '%s:', $region_name ) );

			// Render the differences for the region.
			$differences = $region_obj->get_differences();
			foreach ( $differences as $slug => $difference ) {
				$this->show_difference( $slug, $difference );
			}
		}

	}

	/**
	 * Validate the provided state file against each region's schema.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : State file to load
	 *
	 * @subcommand validate
	 *
	 * @param array $args Args.
	 * @param array $assoc_args Assoc args.
	 */
	public function validate( $args, $assoc_args ) {

		list( $file ) = $args;

		$yaml = $this->load_state_file( $file );

		$this->validate_state_data( $yaml );

		WP_CLI::success( 'State validates against the schema.' );

	}

	/**
	 * List registered states.
	 *
	 * @subcommand list-states
	 *
	 * @param array $args Args.
	 * @param array $assoc_args Assoc args.
	 */
	public function list_states( $args, $assoc_args ) {

		$states = Dictator::get_states();

		$items = array();
		foreach ( $states as $name => $attributes ) {

			$state_obj = new $attributes['class']();
			$regions   = implode( ',', array_keys( $state_obj->get_regions() ) );

			$items[] = (object) array(
				'state'   => $name,
				'regions' => $regions,
			);
		}

		$formatter = new \WP_CLI\Formatter( $assoc_args, array( 'state', 'regions' ) );
		$formatter->display_items( $items );
	}

	/**
	 * Load a given Yaml state file
	 *
	 * @param string $file Filename to load state from.
	 * @return object
	 */
	private function load_state_file( $file ) {

		if ( ! file_exists( $file ) ) {
			WP_CLI::error( sprintf( "File doesn't exist: %s", $file ) );
		}

		$yaml = Mustangostang\Spyc::YAMLLoadString( file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( empty( $yaml ) ) {
			WP_CLI::error( sprintf( "Doesn't appear to be a Yaml file: %s", $file ) );
		}

		return $yaml;
	}

	/**
	 * Validate the provided state file against each region's schema.
	 *
	 * @param array $yaml Data from the state file.
	 */
	private function validate_state_data( $yaml ) {

		if ( empty( $yaml['state'] )
			|| ! Dictator::is_valid_state( $yaml['state'] ) ) {
			WP_CLI::error( 'Incorrect state.' );
		}

		$yaml_data = $yaml;
		unset( $yaml_data['state'] );

		$state_obj = Dictator::get_state_obj( $yaml['state'], $yaml_data );

		$has_errors = false;
		foreach ( $state_obj->get_regions() as $region ) {

			$translator = new \Dictator\Dictator_Translator( $region );
			if ( ! $translator->is_valid_state_data() ) {
				foreach ( $translator->get_state_data_errors() as $error_message ) {
					WP_CLI::warning( $error_message );
				}
				$has_errors = true;
			}
		}

		if ( $has_errors ) {
			WP_CLI::error( "State doesn't validate." );
		}

		return true;

	}

	/**
	 * Write a state object to a file
	 *
	 * @param array  $state_data State Data.
	 * @param string $file Filename to write to.
	 */
	private function write_state_file( $state_data, $file ) {

		$spyc      = new Mustangostang\Spyc();
		$file_data = $spyc->dump( $state_data, 2, 0 );
		// Remove prepended "---\n" from output of the above call.
		$file_data = substr( $file_data, 4 );
		file_put_contents( $file, $file_data ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
	}

	/**
	 * Visually depict the difference between "dictated" and "current"
	 *
	 * @param string $slug Slug.
	 * @param array  $difference Difference to show.
	 */
	private function show_difference( $slug, $difference ) {

		$this->output_nesting_level = 0;

		// Data already exists within WordPress.
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
	 *
	 * @param mixed      $dictated Dictated state.
	 * @param mixed|null $current Current state.
	 */
	private function recursively_show_difference( $dictated, $current = null ) {

		$this->output_nesting_level++;

		if ( $this->is_assoc_array( $dictated ) ) {

			foreach ( $dictated as $key => $value ) {

				if ( $this->is_assoc_array( $value ) || is_array( $value ) ) {

					$new_current = isset( $current[ $key ] ) ? $current[ $key ] : null;
					if ( $new_current ) {
						$this->nested_line( $key . ': ' );
					} else {
						$this->add_line( $key . ': ' );
					}

					$this->recursively_show_difference( $value, $new_current );

				} elseif ( is_string( $value ) ) {

					$pre = $key . ': ';

					if ( isset( $current[ $key ] ) && $current[ $key ] !== $value ) {

						$this->remove_line( $pre . $current[ $key ] );
						$this->add_line( $pre . $value );

					} elseif ( ! isset( $current[ $key ] ) ) {

						$this->add_line( $pre . $value );

					}
				}
			}
		} elseif ( is_array( $dictated ) ) {

			foreach ( $dictated as $value ) {

				if ( ! $current
					|| ! in_array( $value, $current, true ) ) {
					$this->add_line( '- ' . $value );
				}
			}
		} elseif ( is_string( $value ) ) {

			$pre = $key . ': ';

			if ( isset( $current[ $key ] ) && $current[ $key ] !== $value ) {

				$this->remove_line( $pre . $current[ $key ] );
				$this->add_line( $pre . $value );

			} elseif ( ! isset( $current[ $key ] ) ) {

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
	 * @param string $line Line to add.
	 */
	private function add_line( $line ) {
		$this->nested_line( $line, 'add' );
	}

	/**
	 * Output a line to be removed
	 *
	 * @param string $line Line to remove.
	 */
	private function remove_line( $line ) {
		$this->nested_line( $line, 'remove' );
	}

	/**
	 * Output a line that's appropriately nested
	 *
	 * @param string     $line Line to show.
	 * @param mixed|bool $change Whether to display green or red. 'add' for green, 'remove' for red.
	 */
	private function nested_line( $line, $change = false ) {

		if ( 'add' === $change ) {
			$color = '%G';
			$label = '+ ';
		} elseif ( 'remove' === $change ) {
			$color = '%R';
			$label = '- ';
		} else {
			$color = false;
			$label = false;
		}

		\cli\Colors::colorize( '%n' );

		$spaces = ( $this->output_nesting_level * 2 ) + 2;
		if ( $color && $label ) {
			$line   = \cli\Colors::colorize( "{$color}{$label}" ) . $line . \cli\Colors::colorize( '%n' );
			$spaces = $spaces - 2;
		}
		WP_CLI::line( str_pad( ' ', $spaces ) . $line );
	}

	/**
	 * Whether or not this is an associative array
	 *
	 * @param array $array Array to check.
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
