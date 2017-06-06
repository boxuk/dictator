Feature: Network Sites Region

  Scenario: Impose Network Sites
    Given a WP multisite install
    And a network-state.yml file:
      """
      state: network
      sites:
        enolagay:
          title: Enola Gay
          description: Just another B-29 Superfortress bomber
          active_theme: p2
          active_plugins:
            - akismet/akismet.php
          timezone_string: Europe/London
          WPLANG: en_GB
      """

    When I run `wp plugin install akismet --force`
    And I run `wp theme install p2 --force`
    Then STDOUT should not be empty

    When I run `wp language core install en_GB`
    Then STDOUT should not be empty

    When I run `wp dictator impose network-state.yml`
    Then STDOUT should not be empty

    When I run `wp dictator compare network-state.yml`
    Then STDOUT should be empty

    When I run `wp --url=example.com/enolagay option get blogname`
    Then STDOUT should be:
      """
      Enola Gay
      """

    When I run `wp --url=example.com/enolagay option get blogdescription`
    Then STDOUT should be:
      """
      Just another B-29 Superfortress bomber
      """

    When I run `wp --url=example.com/enolagay option get stylesheet`
    Then STDOUT should be:
      """
      p2
      """

    When I run `wp --url=example.com/enolagay plugin list --fields=name,status`
    Then STDOUT should be a table containing rows:
      | name     | status            |
      | akismet  | active            |

    When I run `wp --url=example.com/enolagay option get timezone_string`
    Then STDOUT should be:
      """
      Europe/London
      """

    When I run `wp --url=example.com/enolagay option get WPLANG`
    Then STDOUT should be:
      """
      en_GB
      """
