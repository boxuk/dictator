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
      """

    When I run `wp dictator impose network-state.yml`
    Then STDOUT should not be empty

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
