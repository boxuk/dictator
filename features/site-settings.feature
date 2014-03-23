Feature: Site Settings Region

  Scenario: Impose Site Settings
    Given a WP install
    And a site-state.yml file:
      """
      state: site
      settings:
        title: Salty WordPress
        description: Just another awesome WordPress site
        date_format: F j, Y
        time_format: g:i a
        active_theme: twentyfourteen
      """

    When I run `wp dictator impose site-state.yml`
    Then STDOUT should not be empty

    When I run `wp option get blogname`
    Then STDOUT should be:
      """
      Salty WordPress
      """

    When I run `wp option get stylesheet`
    Then STDOUT should be:
      """
      twentyfourteen
      """
