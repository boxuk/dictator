<?php
/**
 * Dictator controls the State of WordPress with WP-CLI.
 */

declare(strict_types=1);

use BoxUk\Dictator\Command;
use BoxUk\Dictator\Dictator;
use BoxUk\Dictator\State\Network;
use BoxUk\Dictator\State\Site;

if (! defined('WP_CLI') || ! WP_CLI) {
    return;
}

if (! defined('DICTATOR')) {
    define('DICTATOR', true);
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

WP_CLI::add_command('dictator', Command::class);

Dictator::addState('network', Network::class);
Dictator::addState('site', Site::class);
