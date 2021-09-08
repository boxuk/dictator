<?php

namespace Dictator\Regions;

/**
 * Control the sites on the network
 */
class Network_Sites extends Region {

	/**
	 * Schema config.
	 *
	 * @var array $schema
	 */
	protected $schema = array(
		'_type'         => 'prototype',
		'_get_callback' => 'get_sites',
		'_prototype'    => array(
			'_type'     => 'array',
			'_children' => array(
				'custom_domain'   => array(
					'_type'         => 'text',
					'_required'     => false,
					'_get_callback' => 'get_site_value',
				),
				'title'           => array(
					'_type'         => 'text',
					'_required'     => false,
					'_get_callback' => 'get_site_value',
				),
				'description'     => array(
					'_type'         => 'text',
					'_required'     => false,
					'_get_callback' => 'get_site_value',
				),
				'active_theme'    => array(
					'_type'         => 'text',
					'_required'     => false,
					'_get_callback' => 'get_site_value',
				),
				'active_plugins'  => array(
					'_type'         => 'array',
					'_required'     => false,
					'_get_callback' => 'get_site_value',
				),
				'users'           => array(
					'_type'         => 'array',
					'_required'     => false,
					'_get_callback' => 'get_site_value',
				),
				'timezone_string' => array(
					'_type'         => 'text',
					'_required'     => false,
					'_get_callback' => 'get_site_value',
				),
				'WPLANG'          => array(
					'_type'         => 'text',
					'_required'     => false,
					'_get_callback' => 'get_site_value',
				),
			),
		),
	);

	/**
	 * Object-level cache.
	 *
	 * @var $sites
	 */
	protected $sites;

	/**
	 * Get the differences between declared sites and sites on network
	 *
	 * @return array
	 */
	public function get_differences() {

		if ( isset( $this->differences ) ) {
			return $this->differences;
		}

		$this->differences = array();
		// Check each declared site in state data against WordPress.
		foreach ( $this->get_imposed_data() as $site_label => $site_data ) {

			$custom_domain = isset( $site_data['custom_domain'] ) ? $site_data['custom_domain'] : '';
			$site_slug     = $this->get_site_slug( get_current_site(), $site_label, $custom_domain );
			$site_result   = $this->get_site_difference( $site_slug, $site_data );

			if ( ! empty( $site_result ) ) {
				$this->differences[ $site_label ] = $site_result;
			}
		}

		return $this->differences;

	}

	/**
	 * Impose some state data onto a region
	 *
	 * @param string $key Site slug.
	 * @param array  $value Site data.
	 * @return true|WP_Error
	 */
	public function impose( $key, $value ) {
		$custom_domain = isset( $value['custom_domain'] ) ? $value['custom_domain'] : '';
		$site_slug     = $this->get_site_slug( get_current_site(), $key, $custom_domain );

		$site = $this->get_site( $site_slug );
		if ( ! $site ) {
			$site = $this->create_site( $key, $value );
			if ( is_wp_error( $site ) ) {
				return $site;
			}
		}

		switch_to_blog( $site->blog_id );
		foreach ( $value as $field => $single_value ) {

			switch ( $field ) {

				case 'title':
				case 'description':
					$map = array(
						'title'       => 'blogname',
						'description' => 'blogdescription',
					);
					update_option( $map[ $field ], $single_value );
					break;

				case 'active_theme':
					if ( $single_value !== get_option( 'stylesheet' ) ) {
						switch_theme( $single_value );
					}

					break;

				case 'active_plugins':
					foreach ( $single_value as $plugin ) {

						if ( ! is_plugin_active( $plugin ) ) {
							activate_plugin( $plugin );
						}
					}

					break;

				case 'users':
					foreach ( $single_value as $user_login => $role ) {
						$user = get_user_by( 'login', $user_login );
						if ( ! $user ) {
							continue;
						}

						add_user_to_blog( $site->blog_id, $user->ID, $role );
					}

					break;

				case 'WPLANG':
					add_network_option( $site->blog_id, $field, $single_value );
					break;

				default:
					update_option( $field, $single_value );

					break;

			}
		}
		restore_current_blog();

		return true;

	}

	/**
	 * Get a list of all the sites on the network
	 *
	 * @return array
	 */
	protected function get_sites() {

		if ( isset( $this->sites ) && is_array( $this->sites ) ) {
			return array_keys( $this->sites );
		}

		$args  = array(
			'limit'  => 200,
			'offset' => 0,
		);
		$sites = array();
		if ( ! is_multisite() ) {
			return $this->sites;
		}
		do {

			$sites_results = get_sites( $args );
			$sites         = array_merge( $sites, $sites_results );

			$args['offset'] += $args['limit'];

		} while ( $sites_results );

		$this->sites = array();
		foreach ( $sites as $site ) {
			$site_slug                 = $this->get_site_slug( $site );
			$this->sites[ $site_slug ] = $site;
		}
		return array_keys( $this->sites );
	}

	/**
	 * Get the value on a given site
	 *
	 * @param string $key Key to get value for.
	 * @return mixed
	 */
	protected function get_site_value( $key ) {

		$site_slug = $this->current_schema_attribute_parents[0];
		$site      = $this->get_site( $site_slug );

		switch_to_blog( $site->blog_id );

		switch ( $key ) {

			case 'custom_domain':
				$value = isset( $site->domain ) ? $site->domain : '';
				break;
			case 'title':
			case 'description':
			case 'active_theme':
				$map   = array(
					'title'        => 'blogname',
					'description'  => 'blogdescription',
					'active_theme' => 'stylesheet',
				);
				$value = get_option( $map[ $key ] );
				break;

			case 'active_plugins':
				$value = get_option( $key, array() );
				break;

			case 'users':
				$value = array();

				$site_users = get_users();
				foreach ( $site_users as $site_user ) {
					$value[ $site_user->user_login ] = array_shift( $site_user->roles );
				}
				break;

			case 'WPLANG':
				$value = get_network_option( $site->blog_id, $key );
				break;

			default:
				$value = get_option( $key );
				break;

		}
		restore_current_blog();

		return $value;

	}

	/**
	 * Get the difference of the site data to the site on the network
	 *
	 * @param string $site_slug Site slug.
	 * @param array  $site_data Site data.
	 * @return array|false
	 */
	protected function get_site_difference( $site_slug, $site_data ) {

		$site_result = array(
			'dictated' => $site_data,
			'current'  => array(),
		);

		$sites = $this->get_current_data();

		// If there wasn't a matched site, the site must not exist.
		if ( empty( $sites[ $site_slug ] ) ) {
			return $site_result;
		}

		$site_result['current'] = $sites[ $site_slug ];

		if ( \Dictator::array_diff_recursive( $site_result['dictated'], $site_result['current'] ) ) {
			return $site_result;
		} else {
			return false;
		}

	}

	/**
	 * Get a site by its slug
	 *
	 * @param string $site_slug Site slug.
	 * @return WP_Site|false
	 */
	protected function get_site( $site_slug ) {

		// Maybe prime the cache.
		$this->get_sites();
		if ( ! empty( $this->sites[ $site_slug ] ) ) {
			return $this->sites[ $site_slug ];
		}

		return false;

	}

	/**
	 * Create a new site
	 *
	 * @param string $key Key of site.
	 * @param mixed  $value Value.
	 * @return array|WP_Error
	 */
	protected function create_site( $key, $value ) {

		global $wpdb, $current_site;

		$base    = $key;
		$title   = ucfirst( $base );
		$network = $current_site;
		$meta    = $value;
		if ( ! $network ) {
			$networks = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->site WHERE id = %d", 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( ! empty( $networks ) ) {
				$network = $networks[0];
			}
		}

		// Sanitize.
		if ( preg_match( '|^([a-zA-Z0-9-])+$|', $base ) ) {
			$base = strtolower( $base );
		}

		// If not a subdomain install, make sure the domain isn't a reserved word.
		if ( ! is_subdomain_install() ) {
			$subdirectory_reserved_names = apply_filters( 'subdirectory_reserved_names', array( 'page', 'comments', 'blog', 'files', 'feed' ) );
			if ( in_array( $base, $subdirectory_reserved_names, true ) ) {
				return new \WP_Error( 'reserved-word', 'The following words are reserved and cannot be used as blog names: ' . implode( ', ', $subdirectory_reserved_names ) );
			}
		}

		if ( is_subdomain_install() ) {
			$path   = '/';
			$prefix = '';
			if ( $base !== '' ) {
				$prefix = $base . '.';
			}
			$newdomain = $prefix . preg_replace( '|^www\.|', '', $network->domain );
		} else {
			$newdomain = $network->domain;
			$path      = '/' . trim( $base, '/' ) . '/';
		}

		// Custom domain trumps all.
		if ( ! empty( $value['custom_domain'] ) ) {
			$newdomain = $value['custom_domain'];
			$path      = '/';
			unset( $value['custom_domain'] );
		}

		$user_id      = 0;
		$super_admins = get_super_admins();
		if ( ! empty( $super_admins ) && is_array( $super_admins ) ) {
			// Just get the first one.
			$super_login = $super_admins[0];
			$super_user  = get_user_by( 'login', $super_login );
			if ( $super_user ) {
				$user_id = $super_user->ID;
			}
		}

		$wpdb->hide_errors();
		$id = wpmu_create_blog( $newdomain, $path, $title, $user_id, $meta, $network->id );
		$wpdb->show_errors();

		if ( is_wp_error( $id ) ) {
			return $id;
		} else {
			// Reset our internal cache.
			unset( $this->sites );
			return $this->get_site( $this->get_site_slug( get_site( $id ) ) );
		}

	}

	/**
	 * Use the domain plus path for the slug of or sites array. We can pass a key to overwrite path,
	 * we can pass a custom domain which overwrites the domain and 'resets' the path.
	 *
	 * @param \WP_Site | \WP_Network $site_or_network A site or network object.
	 * @param string                 $key A key to overwrite path if not using a custom domain.
	 * @param string                 $custom_domain A custom domain to overwrite the domain and reset the path.
	 */
	protected function get_site_slug( $site_or_network, $key = '', $custom_domain = '' ) {
		$domain = $site_or_network->domain;
		$path   = $key !== '' ? '/' . $key : $site_or_network->path;

		if ( ! empty( $custom_domain ) && $domain !== $custom_domain ) {
			$domain = $custom_domain;
			$path   = '/';
		}

		if ( $path !== '/' && is_subdomain_install() ) {
			return trim( $path . '.' . $domain, '/' );
		}

		return trim( $domain . $path, '/' );
	}

}
