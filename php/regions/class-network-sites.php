<?php

namespace Dictator\Regions;

/**
 * Control the sites on the network
 */

class Network_Sites extends Region {

	protected $sites;

	protected $site_fields = array(
		'title',
		'description',
		'active_theme',
		'users',
		);

	protected $blog_ids = array();

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
		// Check each declared site in state data against WordPress
		foreach( $this->get_imposed_data() as $site_slug => $site_data ) {

			$site_result = $this->get_site_difference( $site_slug, $site_data );

			if ( ! empty( $site_result ) ) {
				$this->differences[ $site_slug ] = $site_result;
			}

		}

		return $this->differences;

	}

	/**
	 * Impose some state data onto a region
	 * 
	 * @param string $key Site slug
	 * @param array $value Site data
	 * @return true|WP_Error
	 */
	public function impose( $key, $value ) {

		$site = $this->get_site( $key );
		if ( ! $site ) {
			$site = $this->create_site( $key, $value );
			if ( is_wp_error( $site ) ) {
				return $site;
			}
		}

		switch_to_blog( $this->blog_ids[ $key ] );
		foreach( $value as $field => $single_value ) {

			switch ( $field ) {

				case 'title':
				case 'description':

					$map = array(
						'title'         => 'blogname',
						'description'   => 'blogdescription',
						);

					update_option( $map[ $field ], $single_value );
					break;

				case 'active_theme':

					if ( $single_value !== get_option( 'stylesheet' ) ) {
						switch_theme( $single_value );
					}

					break;

				case 'users':

					foreach( $single_value as $user_login => $role ) {
						$user = get_user_by( 'login', $user_login );
						if ( ! $user ) {
							continue;
						}

						add_user_to_blog( (int) $this->blog_ids[ $key ], $user->ID, $role );
					}

					break;

			}

		}
		restore_current_blog();

		return true;

	}

	/**
	 * Get the current data for the site region
	 * 
	 * @return array
	 */
	public function get_current_data() {
	
		if ( isset( $this->sites ) ) {
			return $this->sites;
		}

		$args = array(
			'limit'     => 200,
			'offset'    => 0,
			);
		$sites = array();
		do {

			$sites_results = wp_get_sites( $args );
			$sites = array_merge( $sites, $sites_results );

			$args['offset'] += $args['limit'];

		} while( $sites_results );

		$this->sites = array();
		foreach( $sites as $site ) {

			$site_slug = str_replace( '.' . get_current_site()->domain, '', $site['domain'] );

			$this->blog_ids[ $site_slug ] = $site['blog_id'];

			switch_to_blog( $site['blog_id'] );
			foreach( $this->site_fields as $field ) {

				switch ( $field ) {

					case 'title':
					case 'description':

						$map = array(
							'title'         => 'blogname',
							'description'   => 'blogdescription',
							);

						$value = get_option( $map[ $field ] );
						break;

					case 'active_theme':

						$value = get_option( 'stylesheet' );

						break;

					case 'users':

						$value = array();

						$site_users = get_users();
						foreach( $site_users as $site_user ) {
							$value[ $site_user->user_login ] = array_shift( $site_user->roles );
						}

						break;

				}

				$this->sites[ $site_slug ][ $field ] = $value;

			}
			restore_current_blog();

		}

		return $this->sites;
	}

	/**
	 * Get the difference of the site data to the site on the network
	 * 
	 * @param string $site_slug
	 * @param array $site_data
	 * @return array|false
	 */
	protected function get_site_difference( $site_slug, $site_data ) {

		$site_result = array(
			'dictated'        => $site_data,
			'current'         => array(),
		);

		$matched_site = $this->get_site( $site_slug );

		// If there wasn't a matched site, the site must not exist
		if ( empty( $matched_site ) ) {
			return $site_result;
		}

		$site_result['current'] = $matched_site;

		if ( \Dictator::array_diff_recursive( $site_result['dictated'], $site_result['current'] ) ) {
			return $site_result;
		} else {
			return false;
		}

	}

	/**
	 * Get a site by its slug
	 * 
	 * @param string $slug
	 * @return array|false
	 */
	protected function get_site( $site_slug ) {

		$sites = $this->get_current_data();
		if ( ! empty( $sites[ $site_slug ] ) ) {
			return $sites[ $site_slug ];
		}

		return false;

	}

	/**
	 * Create a new site
	 *
	 * @param string $site_slug
	 * @param mixed $value
	 * @return array|WP_Error
	 */
	protected function create_site( $key, $value ) {

		global $wpdb;

		$base = $key;
		$title = ucfirst( $base );
		$network = wpmu_current_site();
		if ( ! $network ) {
			$networks = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->site WHERE id = %d", 1 ) );
			if ( ! empty( $networks ) ) {
				$network = $networks[0];
			}
		}

		// Sanitize
		if ( preg_match( '|^([a-zA-Z0-9-])+$|', $base ) ) {
			$base = strtolower( $base );
		}

		// If not a subdomain install, make sure the domain isn't a reserved word
		if ( ! is_subdomain_install() ) {
			$subdirectory_reserved_names = apply_filters( 'subdirectory_reserved_names', array( 'page', 'comments', 'blog', 'files', 'feed' ) );
			if ( in_array( $base, $subdirectory_reserved_names ) ) {
				return new \WP_Error( 'reserved-word', 'The following words are reserved and cannot be used as blog names: ' . implode( ', ', $subdirectory_reserved_names ) );
			}
		}

		if ( is_subdomain_install() ) {
			$path = '/';
			$url = $newdomain = $base.'.'.preg_replace( '|^www\.|', '', $network->domain );
		} else {
			$newdomain = $network->domain;
			$path = '/' . trim( $base, '/' ) . '/';
			$url = $network->domain . $path;
		}

		$user_id = 0;
		$super_admins = get_super_admins();
		if ( ! empty( $super_admins ) && is_array( $super_admins ) ) {
			// Just get the first one
			$super_login = $super_admins[0];
			$super_user = get_user_by( 'login', $super_login );
			if ( $super_user ) {
				$user_id = $super_user->ID;
			}
		}

		$wpdb->hide_errors();
		$id = wpmu_create_blog( $newdomain, $path, $title, $user_id, array( 'public' => $public ), $network->id );
		$wpdb->show_errors();

		if ( is_wp_error( $id ) ) {
			return $id;
		} else {
			// Reset our internal cache
			unset( $this->sites );
			return $this->get_site( $base );
		}

	}
	
}
