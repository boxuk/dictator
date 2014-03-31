<?php

namespace Dictator\States;

class Network extends State {

	protected $regions = array(
		'settings' => '\Dictator\Regions\Network_Settings',
		'users'    => '\Dictator\Regions\Network_Users',
		'sites'    => '\Dictator\Regions\Network_Sites',
		);
	
}