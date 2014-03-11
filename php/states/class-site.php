<?php

namespace Dictator\States;

class Site extends State {

	protected $regions = array(
		'settings'   => '\Dictator\Regions\Site_Settings',
		'users'      => '\Dictator\Regions\Site_Users',
		'terms'      => '\Dictator\Regions\Terms',
		);

}