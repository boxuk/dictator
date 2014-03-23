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
	 * Schema for the region
	 */
	protected $schema = array();

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
	 * Get the schema for this region
	 * 
	 * @return array
	 */
	public function get_schema() {
		return $this->schema;
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

	/**
	 * Get the current data for the region
	 * 
	 * @return array
	 */
	abstract public function get_current_data();

	/**
	 * Get the imposed data for the region
	 */
	public function get_imposed_data() {

		return $this->data;

	}

}
