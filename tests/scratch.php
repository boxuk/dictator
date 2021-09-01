<?php

use BoxUk\Dictator\Region\WooCommerceGeneral;
use BoxUk\Dictator\State\Network;
use BoxUk\Dictator\Region\SiteSettings;
use BoxUk\Dictator\Region\WooCommerce;
use BoxUk\Dictator\State\Site;

require_once __DIR__ . '/../vendor/autoload.php';

$network = new Network('network');
$site1 = new Site('site1');
$site2 = new Site('site2');
$innerState = new Site('inner_state');
$innerInnerState = new Site('inner_inner_state');

$site2->addState($innerState);

$settings = new SiteSettings('settings');
$woocommerce = new WooCommerce('woocommerce');
$general = new WooCommerceGeneral('general');

$woocommerce->addRegion($general);

$site1->addRegion($settings);
$site1->addRegion($woocommerce);

$site2->addRegion($settings);
$innerState->addRegion($settings);
$innerState->addRegion($woocommerce);

$innerState->addState($innerInnerState);

$innerInnerState->addRegion($settings);

$network->addState($site1);
$network->addState($site2);

var_dump($network->toArray());
