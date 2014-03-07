<?php

namespace Dictator\States;

class Network extends State {

	protected $regions = array(
		'users' => '\Dictator\Regions\Users',
		'sites' => '\Dictator\Regions\Network_Sites',
		);

	
}