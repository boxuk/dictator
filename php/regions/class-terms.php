<?php

namespace Dictator\Regions;

class Terms extends Region {

	/**
	 * Schema config.
	 *
	 * @var array $schema
	 */
	protected $schema = array(
		'_type'         => 'prototype',
		'_get_callback' => 'get_taxonomies',
		'_prototype'    => array(
			'_type'         => 'prototype',
			'_get_callback' => 'get_terms',
			'_prototype'    => array(
				'_type'     => 'array',
				'_children' => array(
					'name'        => array(
						'_type'            => 'text',
						'_required'        => false,
						'_get_callback'    => 'get_term_value',
						'_update_callback' => '',
					),
					'description' => array(
						'_type'            => 'text',
						'_required'        => false,
						'_get_callback'    => 'get_term_value',
						'_update_callback' => '',
					),
					'parent'      => array(
						'_type'            => 'text',
						'_required'        => false,
						'_get_callback'    => 'get_term_value',
						'_update_callback' => '',
					),
				),
			),
		),
	);

	/**
	 * Object-level cache of the term data
	 *
	 * @var array $terms
	 */
	protected $terms = array();

	/**
	 * Impose some data onto the region
	 * How the data is interpreted depends
	 * on the region
	 *
	 * @param string $key Key of the data to impose.
	 * @param mixed  $value Value to impose.
	 * @return true|WP_Error
	 */
	public function impose( $key, $value ) {

		if ( ! taxonomy_exists( $key ) ) {
			return new \WP_Error( 'invalid-taxonomy', 'Invalid taxonomy' );
		}

		foreach ( $value as $slug => $term_values ) {

			$term = get_term_by( 'slug', $slug, $key );
			if ( ! $term ) {

				$ret = wp_insert_term( $slug, $key );
				if ( is_wp_error( $ret ) ) {
					return $ret;
				}
				$term = get_term_by( 'id', $ret['term_id'], $key );
			}

			foreach ( $term_values as $yml_field => $term_value ) {

				switch ( $yml_field ) {
					case 'name':
					case 'description':
						if ( $term_value === $term->$yml_field ) {
							break;
						}

						wp_update_term( $term->term_id, $key, array( $yml_field => $term_value ) );

						break;

					case 'parent':
						if ( $term_value ) {

							$parent_term = get_term_by( 'slug', $term_value, $key );
							if ( ! $parent_term ) {
								return new \WP_Error( 'invalid-parent', sprintf( 'Parent is invalid for term: %s', $slug ) );
							}

							if ( $parent_term->term_id === $term->parent ) {
								break;
							}

							wp_update_term( $term->term_id, $key, array( 'parent' => $parent_term->term_id ) );
						} elseif ( ! $term_value && $term->parent ) {
							wp_update_term( $term->term_id, $key, array( 'parent' => 0 ) );
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
		// Check each declared term in state data against WordPress.
		foreach ( $this->get_imposed_data() as $taxonomy => $taxonomy_data ) {

			$result = $this->get_taxonomy_difference( $taxonomy, $taxonomy_data );

			if ( ! empty( $result ) ) {
				$this->differences[ $taxonomy ] = $result;
			}
		}

		return $this->differences;
	}

	/**
	 * Get the taxonomies on this site
	 *
	 * @return array
	 */
	public function get_taxonomies() {
		return get_taxonomies( array( 'public' => true ) );
	}

	/**
	 * Get the terms associated with a taxonomy on the site
	 *
	 * @param string $taxonomy Taxonomy to get terms for.
	 *
	 * @return array
	 */
	public function get_terms( $taxonomy ) {

		$terms = get_terms( array( $taxonomy ), array( 'hide_empty' => 0 ) );
		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}

		$this->terms[ $taxonomy ] = $terms;

		return wp_list_pluck( $terms, 'slug' );
	}

	/**
	 * Get the value associated with a given term
	 *
	 * @param string $key Key to get term value for.
	 * @return string
	 */
	public function get_term_value( $key ) {

		$taxonomy  = $this->current_schema_attribute_parents[0];
		$term_slug = $this->current_schema_attribute_parents[1];
		foreach ( $this->terms[ $taxonomy ] as $term ) {
			if ( $term->slug === $term_slug ) {
				break;
			}
		}

		switch ( $key ) {

			case 'parent':
				$parent = false;
				foreach ( $this->terms[ $taxonomy ] as $maybe_parent_term ) {
					if ( $maybe_parent_term->term_id === $term->parent ) {
						$parent = $maybe_parent_term;
					}
				}
				if ( ! empty( $parent ) ) {
					$value = $parent->slug;
				} else {
					$value = '';
				}
				break;

			default:
				$value = $term->$key;
				break;

		}

		return $value;
	}

	/**
	 * Get the difference between the declared taxonomy state and
	 * the actual taxonomy state
	 *
	 * @param string $taxonomy Taxonomy to get difference for.
	 * @param array  $taxonomy_data Taxonomy data.
	 * @return array|false
	 */
	protected function get_taxonomy_difference( $taxonomy, $taxonomy_data ) {

		$result = array(
			'dictated' => $taxonomy_data,
			'current'  => array(),
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
