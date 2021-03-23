<?php

namespace Dictator\States;

/**
 * Site class.
 */
class Site extends State {

	/**
	 * Specify regions for a site.
	 *
	 * @var string[]
	 */
	protected $regions = array(
		'settings' => '\Dictator\Regions\Site_Settings',
		'users'    => '\Dictator\Regions\Site_Users',
		'terms'    => '\Dictator\Regions\Terms',
	);

}
