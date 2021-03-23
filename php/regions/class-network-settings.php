<?php

namespace Dictator\Regions;

class Network_Settings extends Region {

	/**
	 * Schema config.
	 *
	 * @var array $schema
	 */
	protected $schema = array(
		'_type'     => 'array',
		'_children' => array(
			'title'                 => array(
				'_type'         => 'text',
				'_required'     => false,
				'_get_callback' => 'get',
			),
			'admin_email'           => array(
				'_type'         => 'email',
				'_required'     => false,
				'_get_callback' => 'get',
			),
			'super_admins'          => array(
				'_type'         => 'array',
				'_required'     => false,
				'_get_callback' => 'get',
			),
			'registration'          => array(
				'_type'         => 'text',
				'_required'     => false,
				'_get_callback' => 'get',
			),
			'notify_registration'   => array(
				'_type'         => 'bool',
				'_required'     => false,
				'_get_callback' => 'get',
			),
			'upload_filetypes'      => array(
				'_type'         => 'text',
				'_required'     => false,
				'_get_callback' => 'get',
			),
			'site_unlimited_upload' => array(
				'_type'         => 'bool',
				'_required'     => false,
				'_get_callback' => 'get',
			),
			'site_upload_space'     => array(
				'_type'         => 'numeric',
				'_required'     => false,
				'_get_callback' => 'get',
			),
			'site_max_upload'       => array(
				'_type'         => 'numeric',
				'_required'     => false,
				'_get_callback' => 'get',
			),
			'enabled_themes'        => array(
				'_type'         => 'array',
				'_required'     => false,
				'_get_callback' => 'get',
			),
			'active_plugins'        => array(
				'_type'         => 'array',
				'_required'     => false,
				'_get_callback' => 'get',
			),
		),
	);

	/**
	 * Correct core's confusing option names
	 *
	 * @var array $options_map
	 */
	protected $options_map = array(
		'title'                 => 'site_name',
		'super_admins'          => 'site_admins',
		'notify_registration'   => 'registrationnotification',
		'site_unlimited_upload' => 'upload_space_check_disabled',
		'site_upload_space'     => 'blog_upload_space',
		'site_max_upload'       => 'fileupload_maxk',
		'enabled_themes'        => 'allowedthemes',
		'active_plugins'        => 'active_sitewide_plugins',
	);

	/**
	 * Impose some data onto the region
	 * How the data is interpreted depends
	 * on the region
	 *
	 * @param null  $_ Unused.
	 * @param array $options Options to impose.
	 *
	 * @return true|WP_Error
	 */
	public function impose( $_, $options ) {

		foreach ( $options as $key => $value ) {

			if ( array_key_exists( $key, $this->options_map ) ) {
				$key = $this->options_map[ $key ];
			}

			switch ( $key ) {
				case 'allowedthemes':
					$allowedthemes = array();
					foreach ( $value as $theme ) {
						$allowedthemes[ $theme ] = true;
					}
					update_site_option( 'allowedthemes', $allowedthemes );
					break;

				case 'active_sitewide_plugins':
					foreach ( $value as $plugin ) {
						activate_plugin( $plugin, '', true );
					}
					break;

				case 'registrationnotification':
					if ( $value ) {
						update_site_option( $key, 'yes' );
					} else {
						update_site_option( $key, 'no' );
					}
					break;

				case 'upload_space_check_disabled':
				case 'blog_upload_space':
				case 'fileupload_maxk':
					update_site_option( $key, intval( $value ) );
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
			'dictated' => $this->get_imposed_data(),
			'current'  => $this->get_current_data(),
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
	 * @param string $name Name to get value for.
	 * @return mixed
	 */
	public function get( $name ) {

		if ( array_key_exists( $name, $this->options_map ) ) {
			$name = $this->options_map[ $name ];
		}

		// Data transformation if we need to.
		switch ( $name ) {
			case 'allowedthemes':
			case 'active_sitewide_plugins':
				// Coerce to array of names.
				return array_keys( get_site_option( $name, array() ) );

			case 'registrationnotification':
				// Coerce to boolean.
				return ( 'yes' === get_site_option( $name ) );
			default:
				return get_site_option( $name );
		}

	}

}
