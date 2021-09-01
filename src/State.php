<?php

namespace BoxUk\Dictator;

abstract class State implements Node
{
    private $name;
    private $states = [];
    private $regions = [];

    public function __construct($name = '')
    {
        $this->name = $name;
    }

    public function addState(State $state)
    {
        $this->states[] = $state;
    }

    public function addRegion(Region $region)
    {
        $this->regions[] = $region;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getStates()
    {
        return $this->states;
    }

    public function hasStates()
    {
        return $this->states !== [];
    }

    /**
     * @return array|Region[]
     */
    public function getRegions(): array
    {
        return $this->regions;
    }

    public function hasRegions()
    {
        return $this->regions !== [];
    }

    public function toArray()
    {
        return [
            $this->getName() => (array)$this->states
        ];
    }

    public function toJson()
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
