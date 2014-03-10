<?php

namespace Dictator\Regions;

class Network_Users extends Region {

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
		foreach( $this->get_imposed_data() as $user_login => $user_data ) {

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
	 * Get the current data for the user region
	 * 
	 * @return array
	 */
	public function get_current_data() {

		if ( ! empty( $this->users ) ) {
			return $this->users;
		}

		$users = get_users( array( 'blog_id' => 0 ) );
		$this->users = array();
		foreach( $users as $user ) {

			foreach( $this->fields as $yml_field => $model_field ) {

				switch ( $yml_field ) {

					case 'display_name':
					case 'email':

						$value = $user->$model_field;
			
						break;

				}

				$this->users[ $user->user_login ][ $yml_field ] = $value;
			}

		}

		return $this->users;
	}

	/**
	 * Get the imposed data for the user region
	 * 
	 * @return array
	 */
	public function get_imposed_data() {

		return $this->data;
		
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



}
