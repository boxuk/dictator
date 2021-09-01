<?php

namespace BoxUk\Dictator\Region;

use BoxUk\Dictator\Context;
use BoxUk\Dictator\Region;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WooCommerceGeneral extends Region
{
    public const KEY = 'general';

    protected function configureData(OptionsResolver $resolver): void
    {
        $resolver->define('currency')
                 ->allowedTypes('string')
                 ->info('Currency for your store');
    }
}
