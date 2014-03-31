# Dictator

Dictator controls the State of WordPress. Strongly influenced by [Salt](http://www.saltstack.com/), Dictator permits configuration attributes stored in YAML state files to be *imposed* upon WordPress.

Dictator's primary concern is to permit the expression of how WordPress is configured as YAML state files. It understands WordPress in terms of *states*. States are collections of *regions*. Each state file has the state declaration, and any tracked configuration details for each region.

For example, the `site` state includes the `settings`, `users`, and `terms` regions. Running `wp dictator export site site-state.yml` against production data will export the production state into a human-readable state file. Apply the state file locally with `wp dictator impose site-state.yml` and you've replicated production without having to download the database.

## Installing

Dictator is a series of [WP-CLI](http://wp-cli.org) commands. Once WP-CLI is installed, Dictator can be installed via [Composer](https://getcomposer.org/), or WP-CLI's `--require` attribute.

## Using

* `dictator compare <file>` - Compare a given state file to the State of WordPress. Produces a colorized diff if differences, otherwise empty output.
* `dictator export <state> <file> [--force]` - Export the State of WordPress to a state file. 
* `dictator impose <file>` - Impose a given state file onto WordPress.
* `dictator validate <file>` - Validate the provided state file against each region's schema.
