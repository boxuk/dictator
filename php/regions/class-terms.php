<?php

namespace Dictator\Regions;

class Terms extends Region {

	private $terms;

	private $fields = array(
		);

	/**
	 * Impose some data onto the region
	 * How the data is interpreted depends
	 * on the region
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return true|WP_Error
	 */
	public function impose( $key, $value ) {

		// @todo 
	}

	/**
	 * Get the differences between the state file and WordPress
	 * 
	 * @return array
	 */
	public function get_differences() {

		$this->differences = array();
		// Check each declared term in state data against WordPress
		foreach( $this->get_imposed_data() as $taxonomy => $taxonomy_data ) {

			$result = $this->get_term_difference( $taxonomy, $taxonomy_data );

			if ( ! empty( $result ) ) {
				$this->differences[ $taxonomy ] = $result;
			}

		}

		return $this->differences;
	}

	/**
	 * Get the current data for the region
	 * 
	 * @return array
	 */
	public function get_current_data() {

		if ( isset( $this->terms ) ) {
			return $this->terms;
		}

		$this->terms = array();
		foreach( get_taxonomies() as $taxonomy ) {

			$formatted_taxonomy_terms = array();
			$taxonomy_terms = get_terms( $taxonomy, array( 'hide_empty' => false ) );

			foreach( $taxonomy_terms as $taxonomy_term ) {

				if ( $taxonomy_term->parent ) {
					$parent_term = wp_filter_object_list( $taxonomy_terms, array( 'term_id' => $taxonomy_term->parent ) );
					$parent_term = array_shift( $parent_term );
				} else {
					$parent_term = false;
				}

				$formatted_taxonomy_terms[ $taxonomy_term->slug ] = array(
					'name'         => $taxonomy_term->name,
					'description'  => $taxonomy_term->description,
					'parent'       => $parent_term ? $parent_term->slug : 
'',					);

			}

			if ( ! empty( $formatted_taxonomy_terms ) ) {
				$this->terms[ $taxonomy ] = $formatted_taxonomy_terms;
			}

		}

		return $this->terms;
	}

	/**
	 * Get the imposed data for the region
	 */
	public function get_imposed_data() {

		return $this->data;

	}



}