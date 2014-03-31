<?php

namespace Dictator\Regions;

class Network_Settings extends Region {

	protected $schema = array(
		'_type'      => 'array',
		'_children'  => array(
			'title'         => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'super_admins'   => array(
				'_type'             => 'array',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			),
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
	public function impose( $_, $options ) {

		foreach( $options as $key => $value ) {

			switch ( $key ) {

				case 'title':
					update_site_option( 'site_name', $value );
					break;

				case 'super_admins':
					update_site_option( 'site_admins', $value );
					break;
				
				default:
					update_site_option( $name, $value );
					break;
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

		$result = array(
			'dictated'        => $this->get_imposed_data(),
			'current'         => $this->get_current_data(),
		);

		if ( \Dictator::array_diff_recursive( $result['dictated'], $result['current'] ) ) {
			return array( 'option' => $result );
		} else {
			return array();
		}
	}

	/**
	 * Get the value for the setting
	 * 
	 * @param string $name
	 * @return mixed
	 */
	public function get( $name ) {

		switch ( $name ) {
			case 'title':
				$value = get_site_option( 'site_name' );
				break;

			case 'super_admins':
				$value = get_site_option( 'site_admins' );
				break;
			
			default:
				$value = get_site_option( $name );
				break;
		}

		return $value;

	}


}