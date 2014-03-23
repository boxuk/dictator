<?php

namespace Dictator\Regions;

abstract class Users extends Region {

	private $users;

	protected $schema = array(
		'_type'       => 'prototype',
		'_prototype'  => array(
			'_type'        => 'array',
			'_children'    => array(
				'display_name'   => array(
					'_type'             => 'text',
					'_required'         => false,
					'_get_callback'     => '',
					'_update_callback'  => '',
					),
				'first_name'     => array(
					'_type'             => 'text',
					'_required'         => false,
					'_get_callback'     => '',
					'_update_callback'  => '',
					),
				'last_name'      => array(
					'_type'             => 'text',
					'_required'         => false,
					'_get_callback'     => '',
					'_update_callback'  => '',
					),
				'email'          => array(
					'_type'             => 'email',
					'_required'         => false,
					'_get_callback'     => '',
					'_update_callback'  => '',
					),
				'role'           => array(
					'_type'             => 'text',
					'_required'         => false,
					'_get_callback'     => '',
					'_update_callback'  => '',
					),
				)
			)
		);

	private $fields = array(
		'display_name'   => 'display_name',
		'email'          => 'user_email',
		'role'           => 'role',
		);

	/**
	 * Get the difference between the state file and WordPress
	 * 
	 * @return array
	 */
	public function get_differences() {

		$this->differences = array();
		// Check each declared user in state data against WordPress
		foreach( $this->get_imposed_data() as $user_login => $user_data ) {

			$result = $this->get_user_difference( $user_login, $user_data );

			if ( ! empty( $result ) ) {
				$this->differences[ $user_login ] = $result;
			}

		}

		return $this->differences;

	}

	/**
	 * Get the current data for the user region
	 * 
	 * @return array
	 */
	public function get_current_data() {

		if ( ! empty( $this->users ) ) {
			return $this->users;
		}

		if ( 'network' == $this->get_context() ) {
			$args['blog_id'] = 0; // all users
		} else {
			$args['blog_id'] = get_current_blog_id();
		}

		$users = get_users( $args );
		$this->users = array();
		foreach( $users as $user ) {

			foreach( $this->fields as $yml_field => $model_field ) {

				// Users have no role in the network context
				// @todo needs a better abstraction
				if ( 'role' == $yml_field && 'network' == $this->get_context() ) {
					continue;
				}

				switch ( $yml_field ) {

					case 'display_name':
					case 'email':

						$value = $user->$model_field;
			
						break;

					case 'role':

						$value = array_shift( $user->roles );

						break;

				}

				$this->users[ $user->user_login ][ $yml_field ] = $value;
			}

		}

		return $this->users;
	}

	/**
	 * Impose some state data onto a region
	 * 
	 * @param string $key User login
	 * @param array $value User's data
	 * @return true|WP_Error
	 */
	public function impose( $key, $value ) {

		// We'll need to create the user if they don't exist
		$user = get_user_by( 'login', $key );
		if ( ! $user ) {
			$user_obj = array(
				'user_login'     => $key,
				'user_email'     => $value['email'], // 'email' is required
				);
			$user_id = wp_insert_user( $user_obj );
			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			// Network users should default to no roles / capabilities
			if ( 'network' == $this->get_context() ) {
				delete_user_option( $user_id, 'capabilities' );
				delete_user_option( $user_id, 'user_level' );
			}

			$user = get_user_by( 'id', $user_id );
		}

		// Update any values needing to be updated
		foreach( $value as $yml_field => $single_value ) {

			// Users have no role in the network context
			// @todo needs a better abstraction
			if ( 'role' == $yml_field && 'network' == $this->get_context() ) {
				continue;
			}

			$model_field = $this->fields[ $yml_field ];

			if ( $user->$model_field != $single_value ) {
				wp_update_user( array( 'ID' => $user->ID, $model_field => $single_value ) );
			}

		}
		return true;

	}

	/**
	 * Get the difference between the declared user and the actual user
	 * 
	 * @param string $user_login
	 * @param array $user_data
	 * @return array|false 
	 */
	protected function get_user_difference( $user_login, $user_data ) {

		$result = array(
			'dictated'        => $user_data,
			'current'         => array(),
		);

		$users = $this->get_current_data();
		if ( ! isset( $users[ $user_login ] ) ) {
			return $result;
		}

		$result['current'] = $users[ $user_login ];

		if ( array_diff_assoc( $result['dictated'], $result['current'] ) ) {
			return $result;
		} else {
			return false;
		}

	}

	/**
	 * Get the context in which this class was called
	 */
	protected function get_context() {
		$class_name = get_class( $this );
		if ( 'Dictator\Regions\Network_Users' == $class_name ) {
			return 'network';
		} else if ( 'Dictator\Regions\Site_Users' == $class_name ) {
			return 'site';
		}
		return false;
	}

}