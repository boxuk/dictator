Feature: Network Settings Region

  Scenario: Impose Network Settings
    Given a WP multisite install
    And a network-state.yml file:
      """
      state: network
      settings:
        title: Dictator Network
      """

    When I run `wp dictator impose network-state.yml`
    Then STDOUT should not be empty

    When I run `wp dictator compare network-state.yml`
    Then STDOUT should be empty

    When I run `wp network-meta get 1 site_name`
    Then STDOUT should be:
      """
      Dictator Network
      """
