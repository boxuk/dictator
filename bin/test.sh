#!/bin/bash

set -ex

# Set up the environment
WP_CLI_DIR=${WP_CLI_DIR-/tmp/wp-cli}

# Run the functional tests
$WP_CLI_DIR/vendor/bin/behat --config=$WP_CLI_DIR/behat.yml features
