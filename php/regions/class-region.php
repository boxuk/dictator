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
	 * Current data in the region
	 */
	protected $current_data;

	/**
	 * Schema for the region
	 */
	protected $schema = array();

	/**
	 * Current schema attribute (used in recursive methods)
	 */
	protected $current_schema_attribute = null;

	/**
	 * Parents of the current schema attribute
	 */
	protected $current_schema_attribute_parents = array();

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
	public function get_current_data() {

		if ( isset( $this->current_data ) ) {
			return $this->current_data;
		}

		$this->current_data = $this->recursively_get_current_data( $this->get_schema() );
		return $this->current_data;
	}

	/**
	 * Get the imposed data for the region
	 */
	public function get_imposed_data() {

		return $this->data;

	}

	/**
	 * Recursively get the current data for the region
	 * 
	 * @return mixed
	 */
	private function recursively_get_current_data( $schema ) {

		switch ( $schema['_type'] ) {

			case 'prototype':

				if ( isset( $schema['_get_callback'] ) ) {
					$prototype_vals = call_user_func( array( $this, $schema['_get_callback'] ), $this->current_schema_attribute );

					$data = array();
					if ( ! empty($prototype_vals) ) {
						foreach( $prototype_vals as $prototype_val ) {
							$this->current_schema_attribute = $prototype_val;

							$this->current_schema_attribute_parents[] = $prototype_val;
							$data[ $prototype_val ] = $this->recursively_get_current_data( $schema['_prototype'] );
							array_pop( $this->current_schema_attribute_parents );

						}
					}
					return $data;
				}

				break;

			case 'array':

				// Arrays can have schemas defined for each child attribute
				if ( ! empty( $schema['_children'] ) ) {

					$data = array();
					foreach( $schema['_children'] as $attribute => $attribute_schema ) {

						$this->current_schema_attribute = $attribute;

						$data[ $attribute ] = $this->recursively_get_current_data( $attribute_schema );

					}
					return $data;

				} else {

					if ( isset( $schema['_get_callback'] ) ) {
						return call_user_func( array( $this, $schema['_get_callback'] ), $this->current_schema_attribute );
					}
					
				}

			case 'text':
			case 'email':
			case 'bool':
			case 'numeric':

				if ( isset( $schema['_get_callback'] ) ) {
					$value = call_user_func( array( $this, $schema['_get_callback'] ), $this->current_schema_attribute );
					if ( $schema['_type'] === 'bool' ) {
						$value = (bool) $value;
					} else if ( $schema['_type'] === 'numeric' ) {
						$value = intval( $value );
					}

					return $value;
				}

				break;

		}

	}

}
