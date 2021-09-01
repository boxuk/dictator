<?php

use BoxUk\Dictator\Dictator;
use BoxUk\Dictator\Normalizer\NetworkNormalizer;
use BoxUk\Dictator\Normalizer\SiteNormalizer;
use BoxUk\Dictator\Processor\StateProcessor;
use BoxUk\Dictator\Region\Settings;
use BoxUk\Dictator\Region\WooCommerce;
use BoxUk\Dictator\Region\WooCommerceGeneral;
use BoxUk\Dictator\State\Site;
use BoxUk\Dictator\Storage\InMemoryStorage;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use BoxUk\Dictator\State\Network;

require_once __DIR__ . '/../vendor/autoload.php';

$encoders = [new YamlEncoder()];
$normalizers = [new NetworkNormalizer(new ObjectNormalizer()), new SiteNormalizer(new ObjectNormalizer())];

$serializer = new Serializer($normalizers, $encoders);

Dictator::registerState(Network::class);
Dictator::registerState(Site::class);
Dictator::registerRegion(Settings::class);
Dictator::registerRegion(WooCommerce::class);
Dictator::registerRegion(WooCommerceGeneral::class);

$data = file_get_contents( __DIR__ . '/network.yaml' );

$type = Site::class;
if (strpos($data, 'network:') === 0) {
    $type = Network::class;
}

/** @var Network $network */
$network = $serializer->deserialize($data, $type, 'yaml');

echo "\nNetwork\n";

$processor = new StateProcessor(new InMemoryStorage());
$processor->process($network);

$data = file_get_contents( __DIR__ . '/site.yaml' );

$type = Site::class;
if (strpos($data, 'network:') === 0) {
    $type = Network::class;
}

/** @var Site $site */
$site = $serializer->deserialize($data, $type, 'yaml');

echo "\nSite\n";

$processor = new StateProcessor(new InMemoryStorage());
$processor->process($site);

