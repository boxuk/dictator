<?php

namespace Dictator;

/**
 * Translation layer between YAML data and WordPress
 */

class Translator {

	protected $region;

	protected $state_data_errors = array();

	protected $current_schema_attribute;

	public function __construct( $region ) {

		$this->region = $region;

	}

	/**
	 * Whether or not the state data provided is valid
	 * 
	 * @return bool
	 */
	public function is_valid_state_data() {

		$this->current_schema_attribute = 'region';

		$this->recursively_validate_state_data( $this->region->get_schema(), $this->region->get_imposed_data() );

		$this->current_schema_attribute = null;

		if ( empty( $this->state_data_errors ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get the errors generated when validating the state data
	 * 
	 * @return array
	 */
	public function get_state_data_errors() {
		return $this->state_data_errors;
	}

	/**
	 * Dive into the schema to see if the provided state data validates
	 * 
	 * @param mixed $schema
	 * @param mixed $state_data
	 */
	protected function recursively_validate_state_data( $schema, $state_data ) {

		if ( ! empty( $schema['_required'] ) && is_null( $state_data ) ) {
			$this->state_data_errors[] = sprintf( "'%s' is required for the region.", $this->current_schema_attribute );
			return;
		} else if ( is_null( $state_data ) ) {
			return;
		}

		switch ( $schema['_type'] ) {

			case 'prototype':

				if ( 'prototype' === $schema['_prototype']['_type'] ) {

					foreach( $state_data as $key => $attribute_data ) {

						$this->current_schema_attribute = $key;

						$this->recursively_validate_state_data( $schema['_prototype']['_prototype'], $attribute_data );
					}

				} else if ( 'array' === $schema['_prototype']['_type'] ) {

					foreach( $state_data as $key => $child_data ) {

						foreach( $schema['_prototype']['_children'] as $schema_key => $child_schema ) {

							$this->current_schema_attribute = $schema_key;

							if ( ! empty( $child_schema['_required'] ) && empty( $child_data[ $schema_key ] ) ) {
								$this->state_data_errors[] = sprintf( "'%s' is required for the region.", $this->current_schema_attribute );
								continue;
							}

							$this->recursively_validate_state_data(
								$child_schema,
								isset( $child_data[ $schema_key ] ) ? $child_data[ $schema_key ] : null
							);
						}

					}

				}

				break;

			case 'array':

				if ( $state_data && ! is_array( $state_data ) ) {
					$this->state_data_errors[] = sprintf( "'%s' needs to be an array.", $this->current_schema_attribute );
				}

				// Arrays can have schemas defined for each child attribute
				if ( ! empty( $schema['_children'] ) ) {

					foreach( $schema['_children'] as $attribute => $attribute_schema ) {

						$this->current_schema_attribute = $attribute;

						$this->recursively_validate_state_data(
							$attribute_schema,
							isset( $state_data[ $attribute ] ) ? $state_data[ $attribute ] : null
						);

					}

				}
					
				break;

			case 'bool':

				if ( ! is_bool( $state_data ) ) {
					$this->state_data_errors[] = sprintf( "'%s' needs to be true or false.", $this->current_schema_attribute );
				}

				break;

			case 'numeric':

				if ( ! is_numeric( $state_data ) ) {
					$this->state_data_errors[] = sprintf( "'%s' needs to be numeric.", $this->current_schema_attribute );
				}

				break;

			case 'text':

				// Nothing to do here
				if ( $state_data && ! is_string( $state_data ) ) {
					$this->state_data_errors[] = sprintf( "'%s' needs to be a string.", $this->current_schema_attribute );
				}

				break;

			case 'email':

				if ( $state_data && ! is_email( $state_data ) ) {
					$this->state_data_errors[] = sprintf( "'%s' needs to be an email address.", $this->current_schema_attribute );
				}

				break;

		}

	}

}
