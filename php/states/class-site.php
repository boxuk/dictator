<?php

namespace Dictator\States;

class Site extends State {

	protected $regions = array(
		'users' => '\Dictator\Regions\Site_Users',
		'terms' => '\Dictator\Regions\Terms',
		);

}