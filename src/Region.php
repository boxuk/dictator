<?php

namespace BoxUk\Dictator;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class Region implements Node
{
    private string $name;
    private array $regions = [];

    private OptionsResolver $dataResolver;

    protected array $data = [];

    public function __construct($name = '')
    {
        $this->name = $name;
        $this->dataResolver = new OptionsResolver();
        $this->configureData($this->dataResolver);
    }

    abstract protected function configureData(OptionsResolver $resolver): void;

    public function addRegion(Region $region)
    {
        $this->regions[] = $region;
    }

    public function getRegions()
    {
        return $this->regions;
    }

    public function hasRegions()
    {
        return $this->regions !== [];
    }

    public function getName()
    {
        return $this->name;
    }

    public function addData(string $key, $value): self
    {
        $this->data[$key] = $value;
        $this->dataResolver->resolve($this->data);

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getTransformedData(): array
    {
        $transformed = [];
        foreach ($this->getData() as $key => $value) {
            if (defined( static::class . '::OPTION_MAP')) {
                $transformed[static::OPTION_MAP[$key] ?? $key] = $value;
            } else {
                $transformed[$key] = $value;
            }
        }

        return $transformed;
    }

    public static function getKey(): string
    {
        if (! defined( static::class . '::KEY')) {
            throw new \RuntimeException('Region ' . static::class . ' must define a key');
        }

        return static::KEY;
    }
}
