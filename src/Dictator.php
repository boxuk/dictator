<?php

namespace BoxUk\Dictator;

class Dictator
{
    private static array $registeredStates = [];
    private static array $registeredRegions = [];

    public static function registerState(string $state): void
    {
        self::$registeredStates[] = $state;
    }

    public static function registerRegion(string $region): void
    {
        self::$registeredRegions[] = $region;
    }

    public static function getRegisteredStates(): array
    {
        return self::$registeredStates;
    }

    public static function getRegisteredRegions(): array
    {
        return self::$registeredRegions;
    }
}
