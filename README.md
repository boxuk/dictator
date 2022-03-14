# Dictator

[![Linting status](https://github.com/boxuk/dictator/actions/workflows/linting.yml/badge.svg)](https://github.com/boxuk/dictator/actions/workflows/linting.yml)
[![Testing status](https://github.com/boxuk/dictator/actions/workflows/testing.yml/badge.svg)](https://github.com/boxuk/dictator/actions/workflows/testing.yml)

## Attribution

- This package was forked from
  [Dictator](https://danielbachhuber.com/2014/03/31/introducing-dictator/)
  with permission due to it seemingly falling into abandonment.
  
## Overview

Dictator controls the State of WordPress. Strongly influenced by [Salt](http://www.saltstack.com/), Dictator permits configuration attributes stored in YAML state files to be *imposed* upon WordPress.

Dictator's primary concern is to permit the expression of how WordPress is configured as YAML state files. It understands WordPress in terms of *states*. States are collections of *regions*. Each state file has the state declaration, and any tracked configuration details for each region.

For example, the `site` state includes the `settings`, `users`, and `terms` regions. Running `wp dictator export site site-state.yml` against production data will export the production state into a human-readable state file:

	state: site
	settings:
	  title: Dictator
	  description: Just another WordPress site controlled by Dictator
	  date_format: F j, Y
	  time_format: g:i a
	  active_theme: twentyfourteen

Apply the state file locally with `wp dictator impose site-state.yml` and you've replicated production without having to download the database.

## Installation

### WP-CLI package

`wp package install boxuk/dictator`

### Composer package

`composer req boxuk/dictator`

## Usage

Dictator comprises these WP-CLI commands:

* `wp dictator compare <file>` - Compare a given state file to the State of WordPress. Produces a colorized diff if differences, otherwise empty output.
* `wp dictator export <state> <file> [--regions=<regions>] [--force]` - Export the State of WordPress to a state file.
* `wp dictator impose <file> [--regions=<regions>]` - Impose a given state file onto WordPress.
* `wp dictator validate <file>` - Validate the provided state file against each region's schema.

First time user? Try Dictator out by calling `wp dictator export site site-state.yml`, making a change to the state file, and seeing a colorized diff of how it compares with `wp dictator compare site-state.yml`.

## Extending

Even though Dictator is in its infancy, it was written with extensibility in mind. New states can be registered with `Dictator::addState();`, and can comprise a selection of existing or custom regions. Write a state / region for your plugin / theme to make it easy for your users to export / impose settings.

### Existing extensions

* [Dictator WooCommerce](https://github.com/boxuk/dictator-woocommerce)
