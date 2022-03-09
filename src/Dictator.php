<?php

declare(strict_types=1);

namespace BoxUk\Dictator;

use BoxUk\Dictator\State\State;

class Dictator
{
    /**
     * Singleton.
     *
     * @var self
     */
    private static self $instance;

    /**
     * States at play.
     *
     * @var array $states
     */
    private array $states = [];

    /**
     * Get the instance of the dictator
     */
    public static function getInstance(): self
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Whether this was called statically
     *
     * @return bool
     */
    private static function calledStatically(): bool
    {
        return ! (isset(self::$instance) && get_class(self::$instance) === __CLASS__);
    }

    /**
     * Register a state that the Dictator can control
     *
     * @param string $name Name of the state.
     * @param string $class Class that represents state's relationship with WP.
     */
    public static function addState(string $name, string $class): void
    {
        if (self::calledStatically()) {
            self::getInstance()->addState($name, $class);
        }

        // @todo validate the class is callable and the schema exists

        $state = [
            'class' => $class,
        ];

        self::$instance->states[ $name ] = $state;
    }

    /**
     * Get all states registered with Dictator
     *
     * @return array
     */
    public static function getStates(): array
    {
        if (self::calledStatically()) {
            return self::getInstance()->getStates();
        }

        return self::$instance->states;
    }

    /**
     * Whether the state is valid
     *
     * @param string $name Name of the state.
     * @return bool
     */
    public static function isValidState(string $name): bool
    {
        if (self::calledStatically()) {
            return self::getInstance()->isValidState($name);
        }

        if (isset(self::$instance->states[ $name ])) {
            return true;
        }

        return false;
    }

    /**
     * Get the object for a given state
     *
     * @param string $name Name of the state.
     * @param array|null  $yaml Data from the state file.
     * @return State|false
     */
    public static function getStateObj(string $name, ?array $yaml = null)
    {
        if (self::calledStatically()) {
            return self::getInstance()->getStateObj($name, $yaml);
        }

        if (! isset(self::$instance->states[ $name ])) {
            return false;
        }

        $class = self::$instance->states[ $name ]['class'];

        return new $class($yaml);
    }
}
