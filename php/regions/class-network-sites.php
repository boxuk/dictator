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
		'users',
		);

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
		foreach( $this->data as $site_slug => $site_data ) {

			$site_result = $this->get_site_difference( $site_slug, $site_data );

			if ( ! empty( $site_result ) ) {
				$this->differences[ $site_slug ] = $site_result;
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
	 * Get the difference of the site data to the site on the network
	 * 
	 * @param string $site_slug
	 * @param array $site_data
	 * @return array|false
	 */
	protected function get_site_difference( $site_slug, $site_data ) {

		$sites = $this->get_sites();
		$matched_site = false;
		foreach( $sites as $site ) {
			$slug = str_replace( '.' . get_current_site()->domain, '', $site['domain'] );
			if ( $site_slug === $slug ) {
				$matched_site = $site;
				break;
			}

		}

		$site_result = array(
			'dictated'        => $site_data,
			'actual'          => array(),
			);

		// If there wasn't a matched site, the site must not exist
		if ( empty( $matched_site ) ) {
			return $site_result;
		}

		switch_to_blog( $matched_site['blog_id'] );
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

				case 'users':

					$site_users = get_users();
					$user_logins = wp_list_pluck( $site_users, 'user_login' );

					// We only care about users present in our state file
					if ( ! empty( $site_result['dictated']['users'] ) ) {

						$value = array_intersect( $site_result['dictated']['users'], $user_logins );

					} else {

						$value = array();

					}

					break;

			}

			$site_result[ 'actual' ] [ $field ] = $value;

		}
		restore_current_blog();

		if ( \Dictator::array_diff_recursive( $site_result['dictated'], $site_result['actual'] ) ) {
			return $site_result;
		} else {
			return false;
		}

	}

	/**
	 * Get the sites on this network
	 *
	 * @return array
	 */
	protected function get_sites() {

		if ( isset( $this->sites ) ) {
			return $this->sites;
		}

		$this->sites = array();
		$args = array(
			'limit'     => 200,
			'offset'    => 0,
			);
		do {

			$sites_results = wp_get_sites( $args );
			$this->sites = array_merge( $this->sites, $sites_results );

			$args['offset'] += $args['limit'];

		} while( $sites_results );

		return $this->sites;
	}
	
}
