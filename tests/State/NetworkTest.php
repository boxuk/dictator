<?php

declare(strict_types=1);

namespace BoxUk\Dictator\Tests\State;

use BoxUk\Dictator\Dictator;
use BoxUk\Dictator\State\Network;

class NetworkTest extends StateTestCase
{
    protected $stateName = 'network';

    public function setUp(): void
    {
        Dictator::addState($this->stateName, Network::class);
    }
}
