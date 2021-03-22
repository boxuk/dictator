Contribute
==========

Hi there! First off, thanks a million for thinking about contributing to Dictator. The community appreciates your time and effort.

Submitting patches
------------------

Whether you want to fix a bug or implement a new feature, the process is pretty much the same:

0. [Search existing issues](https://github.com/boxuk/dictator/issues); if you can't find anything related to what you want to work on, open a new issue so that you can get some initial feedback.
1. [Fork](https://github.com/boxuk/dictator/fork) the repository.
2. Push the code changes from your local clone to your fork.
3. Open a pull request.

It doesn't matter if the code isn't perfect. The idea is to get it reviewed early and iterate on it.

If you're adding a new feature, please add one or more functional tests for it in the `features/` directory. See below.

Lastly, please follow the [WordPress Coding Standards](http://make.wordpress.org/core/handbook/coding-standards/).

Running and writing tests
-------------------------

Dictator uses functional tests, implemented using [Behat](http://behat.org) and leveraging WP-CLI's testing framework. They are located in the `features/` directory.

Before running the functional tests, you'll need to provision the testing environment. 

First make sure you have run composer with dev dependencies:

`composer install`

Then you'll need to export the following vars (update the values accordingly):

`export WP_CLI_TEST_DBROOTUSER=root`
`export WP_CLI_TEST_DBROOTPASS=root`
`export WP_CLI_TEST_DBUSER=wp_cli_test`
`export WP_CLI_TEST_DBPASS=password1`
`export WP_CLI_TEST_DBHOST=localhost`

Finally...
----------

Thanks! Hacking on Dictator should be fun. If you find any of this hard to figure
out, let us know so we can improve our process or documentation!
