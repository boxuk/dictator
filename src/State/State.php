<?php

declare(strict_types=1);

namespace BoxUk\Dictator\State;

use BoxUk\Dictator\Region\InvalidRegionException;
use BoxUk\Dictator\Region\Region;

/**
 * A state controls certain regions of WordPress
 */
abstract class State
{
    /**
     * Data included in the Yaml file
     *
     * @var array|null $yaml
     */
    protected ?array $yaml;

    /**
     * Components of WordPress controlled by this state
     *
     * @var array $regions
     */
    protected array $regions = [];

    /**
     * State constructor.
     *
     * @param array|null $yaml Yaml data.
     */
    public function __construct(?array $yaml = null)
    {
        $this->yaml = $yaml;
    }

    /**
     * Get the regions associated with this state
     *
     * @return Region[]
     * @throws InvalidRegionException If Region is not valid.
     */
    public function getRegions(): array
    {
        $regions = [];
        foreach ($this->regions as $name => $class) {
            $data = ! empty($this->yaml[$name]) ? $this->yaml[$name] : [];

            if (! class_exists($class)) {
                throw new InvalidRegionException(
                    sprintf(
                        'No class "%s" exists for region "%s"',
                        $class,
                        $name
                    )
                );
            }

            $regionObj = new $class($data);

            if (! $regionObj instanceof Region) {
                throw new InvalidRegionException(
                    sprintf(
                        'Class "%s" does not implement Region',
                        $class
                    )
                );
            }

            $regions[ $name ] = $regionObj;
        }
        return $regions;
    }

    /**
     * Get the name of the region
     *
     * @param Region $regionObj Region to get name from.
     * @return string
     */
    public function getRegionName(Region $regionObj): string
    {
        foreach ($this->regions as $name => $class) {
            if (is_a($regionObj, $class)) {
                return $name;
            }
        }

        return '';
    }
}
