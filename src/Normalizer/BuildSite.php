<?php

namespace BoxUk\Dictator\Normalizer;

use BoxUk\Dictator\Dictator;
use BoxUk\Dictator\Region;
use BoxUk\Dictator\State;

trait BuildSite
{
    private function buildSite(State $site, array $data, ?Region $parentRegion = null)
    {
        foreach ($data as $key => $values) {
            $region = $this->regionKeyToObject($key);
            if ($parentRegion !== null) {
                $parentRegion->addRegion($region);
            } else {
                $site->addRegion($region);
            }
            foreach ($values as $k => $val) {
                if ($this->isRegionKey($k)) {
                    $this->buildSite($site, $values, $region);
                } else {
                    $region->addData($k, $val);
                }
            }
        }
        return $site;
    }

    private function regionKeyToObject(string $key): Region {
        $allRegions = Dictator::getRegisteredRegions();

        foreach ($allRegions as $region) {
            if ($region::getKey() === $key) {
                return new $region($key);
            }
        }

        // Last attempt is to see if they are using the class FQN as the key
        if (class_exists($key)) {
            return new $key($key);
        }

        throw new \RuntimeException('Invalid region name: ' . $key);
    }

    private function isRegionKey(string $key): bool
    {
        $allRegions = Dictator::getRegisteredRegions();

        foreach ($allRegions as $region) {
            if ($region::getKey() === $key) {
                return true;
            }
        }

        // Last attempt is to see if they are using the class FQN as the key
        if (class_exists($key)) {
            return true;
        }

        return false;
    }
}
