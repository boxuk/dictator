<?php

declare(strict_types=1);

namespace BoxUk\Dictator;

use cli\Colors;
use Mustangostang\Spyc;
use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI_Command;

/**
 * Dictator controls the State of WordPress.
 */
class Command extends WP_CLI_Command
{
    /**
     * Output nesting level.
     *
     * @var int $outputNestingLevel
     */
    private int $outputNestingLevel = 0;

    /**
     * Export the State of WordPress to a state file.
     *
     * ## OPTIONS
     *
     * <state>
     * : State to export
     *
     * <file>
     * : Where the state should be exported to
     *
     * [--regions=<regions>]
     * : Limit the export to one or more regions.
     *
     * [--force]
     * : Forcefully overwrite an existing state file if one exists.
     *
     * @subcommand export
     *
     * @param array $args Args.
     * @param array $assocArgs Assoc Args.
     *
     * @throws \WP_CLI\ExitException Exits on error, such as bad state supplied.
     */
    public function export(array $args, array $assocArgs): void
    {
        [$state, $file] = $args;

        if (! isset($assocArgs['force']) && file_exists($file)) {
            WP_CLI::confirm('Are you sure you want to overwrite the existing state file?');
        }

        $stateObj = Dictator::getStateObj($state);
        if (! $stateObj) {
            WP_CLI::error('Invalid state supplied.');
        }

        $limitedRegions = ! empty($assocArgs['regions']) ? explode(',', $assocArgs['regions']) : [];

        // Build the state's data.
        $stateData = ['state' => $state];
        foreach ($stateObj->getRegions() as $regionObj) {
            $regionName = $stateObj->getRegionName($regionObj);

            if ($limitedRegions && ! in_array($regionName, $limitedRegions, true)) {
                continue;
            }

            $stateData[ $regionName ] = $regionObj->getCurrentData();
        }

        $this->writeStateFile($stateData, $file);

        WP_CLI::success('State written to file.');
    }

    /**
     * Impose a given state file onto WordPress.
     *
     * ## OPTIONS
     *
     * <file>
     * : State file to impose
     *
     * [--regions=<regions>]
     * : Limit the imposition to one or more regions.
     *
     * @subcommand impose
     *
     * @param array $args Args.
     * @param array $assocArgs Assoc args.
     */
    public function impose(array $args, array $assocArgs): void
    {
        [$file] = $args;

        $yaml = $this->loadStateFile($file);

        $this->validateStateData($yaml);

        $stateObj = Dictator::getStateObj($yaml['state'], $yaml);

        $limitedRegions = ! empty($assocArgs['regions']) ? explode(',', $assocArgs['regions']) : [];

        foreach ($stateObj->getRegions() as $regionObj) {
            $regionName = $stateObj->getRegionName($regionObj);

            if ($limitedRegions && ! in_array($regionName, $limitedRegions, true)) {
                continue;
            }

            if ($regionObj->isUnderAccord()) {
                continue;
            }

            WP_CLI::line(sprintf('%s:', $regionName));

            // Render the differences for the region.
            $differences = $regionObj->getDifferences();
            foreach ($differences as $slug => $difference) {
                $this->showDifference($slug, $difference);

                $to_impose = Utils::arrayDiffRecursive($difference['dictated'], $difference['current']);
                $ret       = $regionObj->impose($slug, $difference['dictated']);
                if (is_wp_error($ret)) {
                    WP_CLI::warning($ret->get_error_message());
                }
            }
        }

        WP_CLI::success('The Dictator has imposed upon the State of WordPress.');
    }

    /**
     * List registered states.
     *
     * @subcommand list-states
     *
     * @param array $args Args.
     * @param array $assocArgs Assoc args.
     */
    public function listStates(array $args, array $assocArgs): void
    {
        $states = Dictator::getStates();

        $items = [];
        foreach ($states as $name => $attributes) {
            $stateObj = new $attributes['class']();
            $regions   = implode(',', array_keys($stateObj->getRegions()));

            $items[] = (object) [
                'state'   => $name,
                'regions' => $regions,
            ];
        }

        $formatter = new Formatter($assocArgs, ['state', 'regions']);
        $formatter->display_items($items);
    }

    /**
     * Compare a given state file to the State of WordPress.
     * Produces a colorized diff if differences, otherwise empty output.
     *
     * ## OPTIONS
     *
     * <file>
     * : State file to compare
     *
     * @subcommand compare
     * @alias diff
     *
     * @param array $args Args.
     * @param array $assocArgs Assoc args.
     */
    public function compare(array $args, array $assocArgs): void
    {
        [$file] = $args;

        $yaml = $this->loadStateFile($file);

        $this->validateStateData($yaml);

        $stateObj = Dictator::getStateObj($yaml['state'], $yaml);

        foreach ($stateObj->getRegions() as $regionName => $regionObj) {
            if ($regionObj->isUnderAccord()) {
                continue;
            }

            WP_CLI::line(sprintf('%s:', $regionName));

            // Render the differences for the region.
            $differences = $regionObj->getDifferences();
            foreach ($differences as $slug => $difference) {
                $this->showDifference($slug, $difference);
            }
        }
    }

    /**
     * Validate the provided state file against each region's schema.
     *
     * ## OPTIONS
     *
     * <file>
     * : State file to load
     *
     * @subcommand validate
     *
     * @param array $args Args.
     * @param array $assocArgs Assoc args.
     */
    public function validate(array $args, array $assocArgs): void
    {
        [$file] = $args;

        $yaml = $this->loadStateFile($file);

        $this->validateStateData($yaml);

        WP_CLI::success('State validates against the schema.');
    }

    /**
     * Load a given Yaml state file
     *
     * @param string $file Filename to load state from.
     * @return array
     */
    private function loadStateFile(string $file): array
    {
        if (! file_exists($file)) {
            WP_CLI::error(sprintf("File doesn't exist: %s", $file));
        }

        $yaml = Spyc::YAMLLoadString(file_get_contents($file)); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        if (empty($yaml)) {
            WP_CLI::error(sprintf("Doesn't appear to be a Yaml file: %s", $file));
        }

        return $yaml;
    }

    /**
     * Validate the provided state file against each region's schema.
     *
     * @param array $yaml Data from the state file.
     * @return void
     */
    private function validateStateData(array $yaml): void
    {
        if (empty($yaml['state']) || ! Dictator::isValidState($yaml['state'])) {
            WP_CLI::error('Incorrect state.');
        }

        $yamlData = $yaml;
        unset($yamlData['state']);

        $stateObj = Dictator::getStateObj($yaml['state'], $yamlData);

        $hasErrors = false;
        foreach ($stateObj->getRegions() as $region) {
            $validator = new Validator($region);
            if (! $validator->isValidStateData()) {
                foreach ($validator->getStateDataErrors() as $errorMessage) {
                    WP_CLI::warning($errorMessage);
                }
                $hasErrors = true;
            }
        }

        if ($hasErrors) {
            WP_CLI::error("State doesn't validate.");
        }
    }

    /**
     * Write a state object to a file
     *
     * @param array  $stateData State Data.
     * @param string $file Filename to write to.
     */
    private function writeStateFile(array $stateData, string $file): void
    {
        $fileData = Spyc::YAMLDump($stateData, 2, 0);
        // Remove prepended "---\n" from output of the above call.
        $fileData = substr($fileData, 4);
        file_put_contents($file, $fileData); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
    }

    /**
     * Visually depict the difference between "dictated" and "current"
     *
     * @param string $slug Slug.
     * @param array  $difference Difference to show.
     * @return void
     */
    private function showDifference(string $slug, array $difference): void
    {
        $this->outputNestingLevel = 0;

        // Data already exists within WordPress.
        if (! empty($difference['current'])) {
            $this->nestedLine($slug . ': ');

            $this->recursivelyShowDifference($difference['dictated'], $difference['current']);
        } else {
            $this->addLine($slug . ': ');

            $this->recursivelyShowDifference($difference['dictated']);
        }

        $this->outputNestingLevel = 0;
    }

    /**
     * Recursively output the difference between "dictated" and "current"
     *
     * @param mixed      $dictated Dictated state.
     * @param mixed|null $current Current state.
     * @return void
     */
    private function recursivelyShowDifference($dictated, $current = null): void
    {
        $this->outputNestingLevel++;

        if (is_array($dictated) && $this->isAssocArray($dictated)) {
            foreach ($dictated as $key => $value) {
                if (is_array($value)) {
                    $newCurrent = $current[$key] ?? null;
                    if ($newCurrent) {
                        $this->nestedLine($key . ': ');
                    } else {
                        $this->addLine($key . ': ');
                    }

                    $this->recursivelyShowDifference($value, $newCurrent);
                } elseif (is_string($value)) {
                    $pre = $key . ': ';

                    if (isset($current[ $key ]) && $current[ $key ] !== $value) {
                        $this->removeLine($pre . $current[ $key ]);
                        $this->addLine($pre . $value);
                    } elseif (! isset($current[ $key ])) {
                        $this->addLine($pre . $value);
                    }
                }
            }
        } elseif (is_array($dictated)) {
            foreach ($dictated as $value) {
                if (! $current || ! in_array($value, $current, true)) {
                    $this->addLine('- ' . $value);
                }
            }
        }

        $this->outputNestingLevel--;
    }

    /**
     * Output a line to be added
     *
     * @param string $line Line to add.
     */
    private function addLine(string $line): void
    {
        $this->nestedLine($line, 'add');
    }

    /**
     * Output a line to be removed
     *
     * @param string $line Line to remove.
     */
    private function removeLine(string $line): void
    {
        $this->nestedLine($line, 'remove');
    }

    /**
     * Output a line that's appropriately nested
     *
     * @param string     $line Line to show.
     * @param mixed|bool $change Whether to display green or red. 'add' for green, 'remove' for red.
     */
    private function nestedLine(string $line, $change = false): void
    {
        if ('add' === $change) {
            $color = '%G';
            $label = '+ ';
        } elseif ('remove' === $change) {
            $color = '%R';
            $label = '- ';
        } else {
            $color = false;
            $label = false;
        }

        Colors::colorize('%n');

        $spaces = ($this->outputNestingLevel * 2) + 2;
        if ($color && $label) {
            $line   = Colors::colorize("{$color}{$label}") . $line . Colors::colorize('%n');
            $spaces -= 2;
        }
        WP_CLI::line(str_pad(' ', $spaces) . $line);
    }

    /**
     * Whether this is an associative array
     *
     * @param array $array Array to check.
     * @return bool
     */
    private function isAssocArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
