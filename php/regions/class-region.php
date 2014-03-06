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
	 * Differences between the state file and WordPress
	 */
	protected $differences;

	public function __construct( $data ) {

		$this->data = $data;

	}

	/**
	 * Whether or not the current state of the region
	 * matches the state file
	 *
	 * @return bool
	 */
	public function is_under_accord() {

		$results = $this->get_differences();
		if ( empty( $results ) ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Impose some data onto the region
	 * How the data is interpreted depends
	 * on the region
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return true|WP_Error
	 */
	abstract public function impose( $key, $value );

	/**
	 * Get the differences between the state file and WordPress
	 * 
	 * @return array
	 */
	abstract public function get_differences();

}
