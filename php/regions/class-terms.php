<?php

namespace Dictator\Regions;

class Terms extends Region {

	private $terms;

	private $fields = array(
		'name'         => 'name',
		'parent'       => 'parent',
		'description'  => 'description',
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

		if ( ! taxonomy_exists( $key ) ) {
			return new \WP_Error( 'invalid-taxonomy', "Invalid taxonomy" );
		}

		foreach( $value as $slug => $term_values ) {

			$term = get_term_by( 'slug', $slug, $key );
			if ( ! $term ) {

				$ret = wp_insert_term( $slug, $key );
				if ( is_wp_error( $ret ) ) {
					return $ret;
				}
				$term = get_term_by( 'id', $ret['term_id'], $key );
			}

			foreach( $this->fields as $yml_field => $model_field ) {

				if ( ! isset( $term_values[ $yml_field ] ) ) {
					continue;
				}

				switch ( $yml_field ) {
					case 'name':
					case 'description':

						if ( $term_values[ $yml_field ] == $term->$model_field ) {
							break;
						}

						wp_update_term( $term->term_id, $key, array( $model_field => $term_values[ $yml_field ] ) );

						break;

					case 'parent':

						if ( $term_values[ $yml_field ] ) {
							
							$parent_term = get_term_by( 'slug', $term_values[ $yml_field ], $key );
							if ( ! $parent_term ) {
								return new \WP_Error( 'invalid-parent', sprintf( "Parent is invalid for term: %s", $slug ) );
							}

							if ( $parent_term->term_id == $term->parent ) {
								break;
							}

							wp_update_term( $term->term_id, $key, array( $model_field => $parent_term->term_id ) );
						} else if ( ! $term_values[ $yml_field ] && $term->parent ) {
							wp_update_term( $term->term_id, $key, array( $model_field => 0 ) );
						}

						break;
				}

			}

		}

		return true;
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

			$result = $this->get_taxonomy_difference( $taxonomy, $taxonomy_data );

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
	 * Get the difference between the declared taxonomy state and
	 * the actual taxonomy state
	 * 
	 * @param string $taxonomy
	 * @param array $taxonomy_data
	 * @return array|false 
	 */
	protected function get_taxonomy_difference( $taxonomy, $taxonomy_data ) {

		$result = array(
			'dictated'        => $taxonomy_data,
			'current'         => array(),
		);

		$current_data = $this->get_current_data();
		if ( ! isset( $current_data[ $taxonomy ] ) ) {
			return $result;
		}

		$result['current'] = $current_data[ $taxonomy ];

		if ( \Dictator::array_diff_recursive( $result['dictated'], $result['current'] ) ) {
			return $result;
		} else {
			return false;
		}

	}



}