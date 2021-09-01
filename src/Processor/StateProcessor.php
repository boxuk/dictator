<?php

namespace BoxUk\Dictator\Processor;

use BoxUk\Dictator\Region;
use BoxUk\Dictator\State;
use BoxUk\Dictator\Storage\Storage;

class StateProcessor implements Processor
{
    private Storage $store;

    public function __construct(Storage $store)
    {
        $this->store = $store;
    }

    public function process(State $state): void
    {
        $regions = $state->getRegions();
        $states = $state->getStates();

        $this->saveRegionsDataRecursive($regions);
        $this->saveStateRegionsDataRecursive($states);

        var_dump($this->store->getAll());
    }

    /**
     * @param array|State[] $states
     * @param array $data
     *
     * @return array
     */
    private function saveStateRegionsDataRecursive(array $states, array $data = []): void
    {
        foreach ($states as $state) {
            if ($state->hasStates()) {
                $this->saveStateRegionsDataRecursive($state->getStates());
            } else {
                if ($state->hasRegions()) {
                    $this->saveRegionsDataRecursive($state->getRegions());
                }
            }
        }
    }

    /**
     * @param array|Region[] $regions
     * @param array $data
     *
     * @return array
     */
    private function saveRegionsDataRecursive(array $regions, array $data = []): void
    {
        foreach ($regions as $region) {
            if ($region->hasRegions()) {
                $this->saveRegionsDataRecursive($region->getRegions());
            } else {
                // Finally at a region we can save.
                $this->store->save([
                    $region->getName() => $region->getTransformedData()
                ]);
            }
        }
    }
}
