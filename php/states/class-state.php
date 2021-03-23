<?php

namespace Dictator\States;

use Dictator\Regions\Region;

/**
 * A state controls certain regions of WordPress
 */
abstract class State {

	/**
	 * Data included in the Yaml file
	 *
	 * @var array $yaml
	 */
	protected $yaml;

	/**
	 * Components of WordPress controlled by this state
	 *
	 * @var array $regions
	 */
	protected $regions = array();

	/**
	 * State constructor.
	 *
	 * @param array|null $yaml Yaml data.
	 */
	public function __construct( $yaml = null ) {

		$this->yaml = $yaml;

	}

	/**
	 * Get the regions associated with this state
	 *
	 * @return array
	 */
	public function get_regions() {

		$regions = array();
		foreach ( $this->regions as $name => $class ) {

			$data = ( ! empty( $this->yaml[ $name ] ) ) ? $this->yaml[ $name ] : array();

			$regions[ $name ] = new $class( $data );

		}
		return $regions;

	}

	/**
	 * Get the name of the region
	 *
	 * @param Region $region_obj Region to get name from.
	 * @return string
	 */
	public function get_region_name( Region $region_obj ) {

		foreach ( $this->regions as $name => $class ) {

			if ( is_a( $region_obj, $class ) ) {
				return $name;
			}
		}

		return '';

	}

	/**
	 * Get the Yaml associated with the State
	 */
	public function get_yaml() {

		// Yaml was passed when the state was instantiated.
		if ( ! is_null( $this->yaml ) ) {
			return $this->yaml;
		}

	}


}
