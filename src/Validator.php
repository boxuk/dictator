<?php

declare(strict_types=1);

namespace BoxUk\Dictator;

use BoxUk\Dictator\Region\Region;

/**
 * Translation layer between YAML data and WordPress
 */
class Validator
{
    /**
     * Region.
     *
     * @var Region $region
     */
    protected Region $region;

    /**
     * State data errors.
     *
     * @var array $stateDataErrors
     */
    protected array $stateDataErrors = [];

    /**
     * Current schema attribute.
     *
     * @var string|null $currentSchemaAttribute
     */
    protected ?string $currentSchemaAttribute;

    /**
     * Validator constructor.
     *
     * @param Region $region Region object.
     */
    public function __construct(Region $region)
    {
        $this->region = $region;
    }

    /**
     * Whether the state data provided is valid
     *
     * @return bool
     */
    public function isValidStateData(): bool
    {
        $this->currentSchemaAttribute = 'region';

        $this->recursivelyValidateStateData($this->region->getSchema(), $this->region->getImposedData());

        $this->currentSchemaAttribute = null;

        return empty($this->stateDataErrors);
    }

    /**
     * Get the errors generated when validating the state data
     *
     * @return array
     */
    public function getStateDataErrors(): array
    {
        return $this->stateDataErrors;
    }

    /**
     * Dive into the schema to see if the provided state data validates
     *
     * @param array $schema Schema to validate against.
     * @param mixed $stateData Data to validate.
     */
    protected function recursivelyValidateStateData(array $schema, $stateData): void
    {
        if (! empty($schema['_required']) && is_null($stateData)) {
            $this->stateDataErrors[] = sprintf("'%s' is required for the region.", $this->currentSchemaAttribute);
            return;
        }

        if (is_null($stateData)) {
            return;
        }

        switch ($schema['_type']) {

            case 'prototype':
                if ('prototype' === $schema['_prototype']['_type']) {
                    foreach ($stateData as $key => $attributeData) {
                        $this->currentSchemaAttribute = $key;

                        $this->recursivelyValidateStateData($schema['_prototype']['_prototype'], $attributeData);
                    }
                } elseif ('array' === $schema['_prototype']['_type']) {
                    foreach ($stateData as $key => $childData) {
                        foreach ($schema['_prototype']['_children'] as $schemaKey => $childSchema) {
                            $this->currentSchemaAttribute = $schemaKey;

                            if (! empty($childSchema['_required']) && empty($childData[ $schemaKey ])) {
                                $this->stateDataErrors[] = sprintf("'%s' is required for the region.", $this->currentSchemaAttribute);
                                continue;
                            }

                            $this->recursivelyValidateStateData(
                                $childSchema,
                                $child_data[$schemaKey] ?? null
                            );
                        }
                    }
                }

                break;

            case 'array':
                if ($stateData && ! is_array($stateData)) {
                    $this->stateDataErrors[] = sprintf("'%s' needs to be an array.", $this->currentSchemaAttribute);
                }

                // Arrays can have schemas defined for each child attribute.
                if (! empty($schema['_children'])) {
                    foreach ($schema['_children'] as $attribute => $attributeSchema) {
                        $this->currentSchemaAttribute = $attribute;

                        $this->recursivelyValidateStateData(
                            $attributeSchema,
                            $stateData[$attribute] ?? null
                        );
                    }
                }

                break;

            case 'bool':
                if (! is_bool($stateData)) {
                    $this->stateDataErrors[] = sprintf("'%s' needs to be true or false.", $this->currentSchemaAttribute);
                }

                break;

            case 'numeric':
                if (! is_numeric($stateData)) {
                    $this->stateDataErrors[] = sprintf("'%s' needs to be numeric.", $this->currentSchemaAttribute);
                }

                break;

            case 'text':
                // Nothing to do here.
                if ($stateData && ! is_string($stateData)) {
                    $this->stateDataErrors[] = sprintf("'%s' needs to be a string.", $this->currentSchemaAttribute);
                }

                break;

            case 'email':
                if ($stateData && ! is_email($stateData)) {
                    $this->stateDataErrors[] = sprintf("'%s' needs to be an email address.", $this->currentSchemaAttribute);
                }

                break;
        }
    }
}
