<?php

namespace Dictator\Regions;

class Site_Settings extends Region {

	protected $schema = array(
		'_type'      => 'array',
		'_children'  => array(
			/**
			 * General
			 */
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
			'admin_email' => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'timezone' => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'WPLANG' => array(
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
			/**
			 * Reading
			 */
			'public'        => array(
				'_type'             => 'bool',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'posts_per_page' => array(
				'_type'             => 'numeric',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'posts_per_feed' => array(
				'_type'             => 'numeric',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'feed_uses_excerpt' => array(
				'_type'             => 'bool',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'show_on_front' => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'page_on_front' => array(
				'_type'             => 'numeric',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'page_for_posts' => array(
				'_type'             => 'numeric',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			/**
			 * Discussion
			 */
			'allow_comments'    => array(
				'_type'             => 'bool',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'allow_pingbacks'    => array(
				'_type'             => 'bool',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'notify_comments'    => array(
				'_type'             => 'bool',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'notify_moderation'  => array(
				'_type'             => 'bool',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			/**
			 * Permalinks
			 */
			'permalink_structure' => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'category_base' => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			'tag_base' => array(
				'_type'             => 'text',
				'_required'         => false,
				'_get_callback'     => 'get',
				),
			/**
			 * Theme / plugins
			 */
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
		'title'              => 'blogname',
		'description'        => 'blogdescription',
		'timezone'           => 'timezone_string',
		'public'             => 'blog_public',
		'posts_per_feed'     => 'posts_per_rss',
		'feed_uses_excerpt'  => 'rss_use_excerpt',
		'allow_comments'     => 'default_comment_status',
		'allow_pingbacks'    => 'default_ping_status',
		'notify_comments'    => 'comments_notify',
		'notify_moderation'  => 'moderation_notify',
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

				// Boolean stored as 0 or 1
				case 'blog_public':
				case 'rss_use_excerpt':
				case 'comments_notify':
				case 'moderation_notify':
					update_option( $key, intval( $value ) );
					break;

				// Boolean stored as 'open' or 'closed'
				case 'default_comment_status':
				case 'default_ping_status':
					if ( $value ) {
						update_option( $key, 'open' );
					} else {
						update_option( $key, 'closed' );
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

		// Data transformation if we need to
		switch ( $name ) {
			case 'default_comment_status':
			case 'default_ping_status':
				$value = ( 'open' === $value ) ? true : false;
				break;

		}

		return $value;

	}

}
