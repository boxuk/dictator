<?php

namespace BoxUk\Dictator\Normalizer;

use BoxUk\Dictator\State\Network;
use BoxUk\Dictator\State\Site;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class NetworkNormalizer implements ContextAwareDenormalizerInterface
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
        $validType = $type === Network::class;
        return is_array($data) && $validType && in_array($format, self::SUPPORTED_TYPES, true);
    }

    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        /** @var Network $object */
        $object = $this->normalizer->denormalize($data, $type, $format, $context);

        foreach ($data as $sites) {
            foreach ($sites as $siteName => $site) {
                // We can set a region on a state and a network is a state, so check if top level is a region first.
                if ($this->isRegionKey($siteName)) {
                    $region = $this->regionKeyToObject($siteName);
                    $object->addRegion($region);
                    foreach ($site as $key => $val) {
                        $region->addData($key, $val);
                    }
                } else {
                    $object->addState($this->buildSite(new Site($siteName), $site));
                }
            }
        }

        return $object;
    }
}
