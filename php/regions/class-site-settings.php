<?php

namespace Dictator\Regions;

class Site_Settings extends Region {

	private $options;

	private $fields = array(
		'title'          => 'blogname',
		'description'    => 'blogdescription',
		'date_format'    => 'date_format',
		'time_format'    => 'time_format',
		'active_theme'   => 'stylesheet',
		'active_plugins' => 'active_plugins', 
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
	 * Get the current data for the region
	 * 
	 * @return array
	 */
	public function get_current_data() {

		$this->options = array();
		foreach( $this->fields as $yml_field => $model_field ) {

			$value = get_option( $model_field );
			if ( $value ) {
				$this->options[ $yml_field ] = $value;
			}

		}

		return $this->options;
	}

	/**
	 * Get the imposed data for the region
	 */
	public function get_imposed_data() {

		return $this->data;

	}

}