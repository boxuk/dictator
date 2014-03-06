<?php

namespace Dictator\Regions;

class Users extends Region {

	private $users;

	private $fields = array(
		'display_name'   => 'display_name',
		'email'          => 'user_email',
		);

	/**
	 * Get the difference between the state file and WordPress
	 * 
	 * @return array
	 */
	public function get_differences() {

		$this->differences = array();
		// Check each declared user in state data against WordPress
		foreach( $this->data as $user_login => $user_data ) {

			$result = $this->get_user_difference( $user_login, $user_data );

			if ( ! empty( $result ) ) {
				$this->differences[ $user_login ] = $result;
			}

		}

		return $this->differences;

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
			delete_user_option( $user_id, 'capabilities' );
			delete_user_option( $user_id, 'user_level' );

			$user = get_user_by( 'id', $user_id );
		}

		// Update any values needing to be updated
		foreach( $value as $yml_field => $single_value ) {

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
			'actual'          => array(),
		);

		$user = get_user_by( 'login', $user_login );
		if ( ! $user ) {
			return $result;
		}

		foreach( $this->fields as $yml_field => $model_field ) {

			switch ( $yml_field ) {

				case 'display_name':
				case 'email':

					$value = $user->$model_field;
		
					break;

			}

			$result[ 'actual' ] [ $yml_field ] = $value;
		}

		if ( array_diff_assoc( $result['dictated'], $result['actual'] ) ) {
			return $result;
		} else {
			return false;
		}

	}



}
