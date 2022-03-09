<?php

declare(strict_types=1);

namespace BoxUk\Dictator\Tests\State;

use BoxUk\Dictator\Dictator;
use BoxUk\Dictator\Region\InvalidRegionException;
use BoxUk\Dictator\Region\Region;
use BoxUk\Dictator\State\State;
use PHPUnit\Framework\TestCase;

abstract class StateTestCase extends TestCase
{
    public function test_get_regions_return_array_of_region_objects(): void
    {
        $regions = Dictator::getStateObj($this->stateName)->getRegions();
        $this->assertIsArray($regions);

        foreach ($regions as $region) {
            $this->assertInstanceOf(Region::class, $region);
        }
    }

    public function test_get_regions_throws_exception_if_regions_contains_invalid_class(): void
    {
        Dictator::addState('teststate', TestStateStub::class);

        $this->expectException(InvalidRegionException::class);
        $this->expectExceptionMessage('No class "BoxUk\Dictator\Tests\State\InvalidClass" exists for region "invalid"');

        Dictator::getStateObj('teststate')->getRegions();
    }
}

class TestStateStub extends State
{
    protected array $regions = [
        'invalid' => InvalidClass::class,
    ];
}
