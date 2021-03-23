<?php

namespace Dictator\States;

class Network extends State {

	/**
	 * Regions for a network state.
	 *
	 * @var string[]
	 */
	protected $regions = array(
		'settings' => '\Dictator\Regions\Network_Settings',
		'users'    => '\Dictator\Regions\Network_Users',
		'sites'    => '\Dictator\Regions\Network_Sites',
	);

}
