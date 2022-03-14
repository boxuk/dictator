<?php

declare(strict_types=1);

namespace BoxUk\Dictator\Tests;

use BoxUk\Dictator\Dictator;
use BoxUk\Dictator\State\InvalidStateException;
use BoxUk\Dictator\State\Network;
use BoxUk\Dictator\State\Site;
use PHPUnit\Framework\TestCase;

class DictatorTest extends TestCase
{
    public function setUp(): void
    {
        Dictator::addState('network', Network::class);
        Dictator::addState('site', Site::class);
    }

    public function test_invalid_state_name_raises_exception(): void
    {
        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('State "invalid" is not registered with Dictator');

        Dictator::getStateObj('invalid');
    }

    public function test_invalid_state_class_raises_exception(): void
    {
        Dictator::addState('invalidstate', 'Invalid\State');

        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('No class "Invalid\State" exists for state "invalidstate"');

        Dictator::getStateObj('invalidstate');
    }

    public function test_invalid_state_object_raises_exception(): void
    {
        Dictator::addState('invalidstate', InvalidStateStub::class);

        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('Class "BoxUk\Dictator\Tests\InvalidStateStub" does not implement State');

        Dictator::getStateObj('invalidstate');
    }
}

class InvalidStateStub
{
}
