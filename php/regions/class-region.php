<?php

namespace Dictator\Regions;

/**
 * An area of WordPress controlled by the dictator
 */
abstract class Region {

	/**
	 * State's data on the region
	 */
	protected $data;

	/**
	 * Difference between the state file and WordPress
	 */
	protected $difference;

	public function __construct( $data ) {

		$this->data = $data;

	}

	/**
	 * Whether or not the current state of the region
	 * matches the state file
	 *
	 * @return bool
	 */
	abstract public function is_under_accord();

	/**
	 * Get the difference between the state file and WordPress
	 * 
	 * @return array
	 */
	abstract public function get_difference();

	/**
	 * Impose The Dictator's will on the region
	 */
	abstract public function dictate();

}
