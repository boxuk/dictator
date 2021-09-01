<?php

namespace BoxUk\Dictator\Processor;

use BoxUk\Dictator\State;

interface Processor
{
    public function process(State $state);
}
