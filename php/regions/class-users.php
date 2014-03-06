<?php

namespace Dictator\Regions;

class Users extends Region {

	private $users;

	private $user_fields = array(
		'name',
		'email',
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
	 * Impose The Dictator's will on the region
	 */
	public function dictate() {

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

		foreach( $this->user_fields as $field ) {

			switch ( $field ) {

				case 'display_name':
				case 'email':

					$map = array(
						'display_name'    => 'display_name',
						'email'           => 'user_email',
						);
					$key = $map[ $field ];

					$value = $user->$key;
		
					break;

			}

			$result[ 'actual' ] [ $field ] = $value;
		}

		if ( array_diff_assoc( $result['dictated'], $result['actual'] ) ) {
			return $result;
		} else {
			return false;
		}

	}



}
