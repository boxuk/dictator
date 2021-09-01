<?php

namespace BoxUk\Dictator\Normalizer;

use BoxUk\Dictator\State\Site;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class SiteNormalizer implements ContextAwareDenormalizerInterface
{
    use BuildSite;

    private const SUPPORTED_TYPES = ['yaml'];
    private $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        $validType = $type === Site::class;
        return is_array($data) && $validType && in_array($format, self::SUPPORTED_TYPES, true);
    }

    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $object = $this->normalizer->denormalize($data, $type, $format, $context);

        foreach ($data as $site => $regions) {
            $this->buildSite($object, $regions);
        }

        return $object;
    }
}
