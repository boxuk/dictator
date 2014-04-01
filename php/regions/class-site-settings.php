<?php

namespace Dictator\Regions;

class Site_Settings extends Region {

	protected $schema = array(
		'_type'      => 'array',
		'_children'  => array(
			'title'         => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'description'   => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'date_format'   => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'time_format'   => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'active_theme'  => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'active_plugins' => array(
				'_type'             => 'array',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			),
		);

	/**
	 * Correct core's confusing option names
	 */
	protected $options_map = array(
		'title'            => 'blogname',
		'description'      => 'blogdescription',
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

			if ( array_key_exists( $key, $this->options_map ) ) {
				$key = $this->options_map[ $key ];
			}

			switch ( $key ) {

				case 'active_theme':
					switch_theme( $value );
					break;

				case 'active_plugins':

					foreach( $value as $plugin ) {

						if ( ! is_plugin_active( $plugin ) ) {
							activate_plugin( $plugin );
						}

					}
					break;
				
				default:
					update_option( $key, $value );
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

		if ( array_key_exists( $name, $this->options_map ) ) {
			$name = $this->options_map[ $name ];
		}

		switch ( $name ) {
			case 'active_theme':
				$value = get_option( 'stylesheet' );
				break;
			
			default:
				$value = get_option( $name );
				break;
		}

		return $value;

	}

}