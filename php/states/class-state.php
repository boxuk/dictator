<?php

namespace Dictator\States;

/**
 * A state controls certain regions of WordPress
 */
abstract class State {

	/**
	 * Data included in the Yaml file
	 */
	protected $yaml;

	/**
	 * Components of WordPress controlled by this state
	 */
	protected $regions = array();

	public function __construct( $yaml ) {

		$this->yaml = $yaml;

	}

	/**
	 * Get the regions associated with this state
	 * 
	 * @return array
	 */
	public function get_regions() {

		$regions = array();
		foreach( $this->regions as $name => $class ) {

			$data = ( ! empty( $this->yaml[ $name ] ) ) ? $this->yaml[ $name ] : array();

			$regions[] = new $class( $data );

		}
		return $regions;

	}

	/**
	 * Get the name of the region
	 * 
	 * @param object
	 * @return string
	 */
	public function get_region_name( \Dictator\Regions\Region $region_obj ) {

		foreach( $this->regions as $name => $class ) {

			if ( is_a( $region_obj, $class ) ) {
				return $name;
			}

		}

		return '';

	} 

	
}