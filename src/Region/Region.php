<?php

declare(strict_types=1);

namespace BoxUk\Dictator\Region;

/**
 * An area of WordPress controlled by the dictator
 */
abstract class Region
{
    /**
     * State's data on the region
     *
     * @var array $data
     */
    protected array $data;

    /**
     * Current data in the region
     *
     * @var $currentData
     */
    protected $currentData;

    /**
     * Schema for the region
     *
     * @var array $schema
     */
    protected array $schema = [];

    /**
     * Current schema attribute (used in recursive methods)
     *
     * @var $currentSchemaAttribute
     */
    protected $currentSchemaAttribute = null;

    /**
     * Parents of the current schema attribute
     *
     * @var array $currentSchemaAttributeParents
     */
    protected array $currentSchemaAttributeParents = [];

    /**
     * Differences between the state file and WordPress
     *
     * @var array $differences
     */
    protected array $differences;

    /**
     * Region constructor.
     *
     * @param array $data Data for the region.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Whether the current state of the region
     * matches the state file
     *
     * @return bool
     */
    public function isUnderAccord(): bool
    {
        $results = $this->getDifferences();
        if (empty($results)) {
            return true;
        }

        return false;
    }

    /**
     * Get the schema for this region
     *
     * @return array
     */
    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * Impose some data onto the region
     * How the data is interpreted depends
     * on the region
     *
     * @param string $key Key of the data to impose.
     * @param mixed  $value Value of the data to impose.
     * @return true|\WP_Error
     */
    abstract public function impose(string $key, $value);

    /**
     * Get the differences between the state file and WordPress
     *
     * @return array
     */
    abstract public function getDifferences(): array;

    /**
     * Get the current data for the region
     *
     * @return array
     */
    public function getCurrentData(): array
    {
        if (isset($this->currentData)) {
            return $this->currentData;
        }

        $this->currentData = $this->recursivelyGetCurrentData($this->getSchema());
        return $this->currentData;
    }

    /**
     * Get the imposed data for the region
     */
    public function getImposedData(): array
    {
        return $this->data;
    }

    /**
     * Recursively get the current data for the region
     *
     * @param array $schema Schema array.
     * @return mixed
     */
    private function recursivelyGetCurrentData(array $schema)
    {
        switch ($schema['_type']) {

            case 'prototype':
                if (isset($schema['_get_callback'])) {
                    $prototypeVals = $this->{$schema['_get_callback']}($this->currentSchemaAttribute);

                    $data = [];
                    if (! empty($prototypeVals)) {
                        foreach ($prototypeVals as $prototypeVal) {
                            $this->currentSchemaAttribute = $prototypeVal;

                            $this->currentSchemaAttributeParents[] = $prototypeVal;
                            $data[ $prototypeVal ] = $this->recursivelyGetCurrentData($schema['_prototype']);
                            array_pop($this->currentSchemaAttributeParents);
                        }
                    }
                    return $data;
                }

                break;

            case 'array':
                // Arrays can have schemas defined for each child attribute.
                if (! empty($schema['_children'])) {
                    $data = [];
                    foreach ($schema['_children'] as $attribute => $attributeSchema) {
                        $this->currentSchemaAttribute = $attribute;

                        $data[ $attribute ] = $this->recursivelyGetCurrentData($attributeSchema);
                    }
                    return $data;
                }

                if (isset($schema['_get_callback'])) {
                    return $this->{$schema['_get_callback']}($this->currentSchemaAttribute);
                }

                break;

            case 'text':
            case 'email':
            case 'bool':
            case 'numeric':
                if (isset($schema['_get_callback'])) {
                    $value = $this->{$schema['_get_callback']}($this->currentSchemaAttribute);
                    if ($schema['_type'] === 'bool') {
                        $value = (bool) $value;
                    } elseif ($schema['_type'] === 'numeric') {
                        $value = (int)$value;
                    }

                    return $value;
                }
                break;
        }
    }
}
