Contribute
==========

Hi there! First off, thanks a million for thinking about contributing to Dictator. The community appreciates your time and effort.

Submitting patches
------------------

Whether you want to fix a bug or implement a new feature, the process is pretty much the same:

0. [Search existing issues](https://github.com/danielbachhuber/dictator/issues); if you can't find anything related to what you want to work on, open a new issue so that you can get some initial feedback.
1. [Fork](https://github.com/wp-cli/wp-cli/fork) the repository.
2. Push the code changes from your local clone to your fork.
3. Open a pull request.

It doesn't matter if the code isn't perfect. The idea is to get it reviewed early and iterate on it.

If you're adding a new feature, please add one or more functional tests for it in the `features/` directory. See below.

Lastly, please follow the [WordPress Coding Standards](http://make.wordpress.org/core/handbook/coding-standards/).

Running and writing tests
-------------------------

Dictator uses functional tests, implemented using [Behat](http://behat.org) and leveraging WP-CLI's testing framework. They are located in the `features/` directory.

Before running the functional tests, you'll need to provision the testing environment. You can do so by running `bash bin/install-package-tests.sh`. Behind the scenes, the script will install WP-CLI, Behat, create a MySQL database, etc.

To run the functional tests, you can use `bash bin/test.sh`. It routes the request to WP-CLI's Behat testing framework.

Finally...
----------

Thanks! Hacking on Dictator should be fun. If you find any of this hard to figure
out, let us know so we can improve our process or documentation!
