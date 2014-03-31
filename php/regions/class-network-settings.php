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
			'registration' => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'upload_filetypes' => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'enabled_themes' => array(
				'_type'             => 'array',
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
		'title'              => 'site_name',
		'super_admins'       => 'site_admins',
		'enabled_themes'     => 'allowedthemes',
		'active_plugins'     => 'active_sitewide_plugins',
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
				case 'allowedthemes':
					$allowedthemes = array();
					foreach( $value as $theme ) {
						$allowedthemes[ $theme ] = true;
					}
					update_site_option( 'allowedthemes', $allowedthemes );
					break;

				case 'active_sitewide_plugins':
					foreach( $value as $plugin ) {
						activate_plugin( $plugin, '', true );
					}
					break;
				
				default:
					update_site_option( $key, $value );
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
		
		$value = get_site_option( $name );

		// Data transformation if we need to
		switch ( $name ) {
			case 'allowedthemes':
			case 'active_sitewide_plugins':
				$value = array_keys( $value );
				break;
		}

		return $value;

	}


}