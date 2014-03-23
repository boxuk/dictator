<?php

namespace Dictator\Regions;

class Site_Settings extends Region {

	protected $schema = array(
		'_type'      => 'array',
		'_children'  => array(
			'title'         => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => '\Dictator\Regions\Site_Settings::get',
				'_update_callback'  => '\Dictator\Regions\Site_Settings::update',
				),
			'description'   => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => '\Dictator\Regions\Site_Settings::get',
				'_update_callback'  => '\Dictator\Regions\Site_Settings::update',
				),
			'date_format'   => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => '\Dictator\Regions\Site_Settings::get',
				'_update_callback'  => '\Dictator\Regions\Site_Settings::update',
				),
			'time_format'   => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => '\Dictator\Regions\Site_Settings::get',
				'_update_callback'  => '\Dictator\Regions\Site_Settings::update',
				),
			'active_theme'  => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => '\Dictator\Regions\Site_Settings::get',
				'_update_callback'  => '\Dictator\Regions\Site_Settings::update',
				),
			'active_plugins' => array(
				'_type'             => 'array',
				'_required'         => false,
				'_get_callback'     => '\Dictator\Regions\Site_Settings::get',
				'_update_callback'  => '\Dictator\Regions\Site_Settings::update',
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

				case 'active_theme':

					if ( $value !== get_option( 'stylesheet' ) ) {
						switch_theme( $value );
					}

					break;

				case 'active_plugins':

					foreach( $value as $plugin ) {

						if ( ! is_plugin_active( $plugin ) ) {
							activate_plugin( $plugin );
						}

					}

					break;
				
				default:
				
					$model_key = $this->fields[ $key ];
					if ( $value != get_option( $this->fields[ $key ] ) ) {
						update_option( $model_key, $value );
					}

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
	public static function get( $name ) {

		switch ( $name ) {
			case 'title':
				$value = get_option( 'blogname' );
				break;

			case 'description':
				$value = get_option( 'blogdescription' );
				break;

			case 'active_theme':
				$value = get_option( 'stylesheet' );
				break;
			
			default:
				$value = get_option( $name );
				break;
		}

		return $value;

	}

	/**
	 * Update the value for the setting
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @return mixed
	 */
	public static function update( $name, $value ) {

		switch ( $name ) {
			case 'title':
				update_option( 'blogname', $value );
				break;

			case 'description':
				update_option( 'blogdescription', $value );
				break;

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
				update_option( $name, $value );
				break;
		}

	}

}