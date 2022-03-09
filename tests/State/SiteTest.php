<?php

declare(strict_types=1);

namespace BoxUk\Dictator\Tests\State;

use BoxUk\Dictator\Dictator;
use BoxUk\Dictator\State\Site;

class SiteTest extends StateTestCase
{
    protected $stateName = 'site';

    public function setUp(): void
    {
        Dictator::addState($this->stateName, Site::class);
    }
}
