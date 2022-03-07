<?php

declare(strict_types=1);

namespace BoxUk\Dictator\State;

use BoxUk\Dictator\Region\SiteSettings;
use BoxUk\Dictator\Region\SiteUsers;
use BoxUk\Dictator\Region\Terms;

/**
 * Site class.
 */
class Site extends State
{
    /**
     * Specify regions for a site.
     *
     * @var string[]
     */
    protected array $regions = [
        'settings' => SiteSettings::class,
        'users' => SiteUsers::class,
        'terms' => Terms::class,
    ];
}
