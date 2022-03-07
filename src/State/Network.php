<?php

declare(strict_types=1);

namespace BoxUk\Dictator\State;

use BoxUk\Dictator\Region\NetworkSettings;
use BoxUk\Dictator\Region\NetworkSites;
use BoxUk\Dictator\Region\NetworkUsers;

class Network extends State
{
    /**
     * Regions for a network state.
     *
     * @var string[]
     */
    protected array $regions = [
        'settings' => NetworkSettings::class,
        'users' => NetworkUsers::class,
        'sites' => NetworkSites::class,
    ];
}
