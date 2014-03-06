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
		foreach( $this->regions as $key => $class ) {

			$data = ( ! empty( $this->yaml[ $key ] ) ) ? $this->yaml[ $key ] : array();

			$regions[] = new $class( $data );

		}
		return $regions;

	}

	
}