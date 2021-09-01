<?php

namespace BoxUk\Dictator\Region;

use BoxUk\Dictator\Region;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WooCommerce extends Region
{
    public const KEY = 'woocommerce';

    protected function configureData(OptionsResolver $resolver): void
    {

    }
}
