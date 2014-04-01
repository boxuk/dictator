Feature: Network Settings Region

  Scenario: Impose Network Settings
    Given a WP multisite install
    And a network-state.yml file:
      """
      state: network
      settings:
        title: Dictator Network
        admin_email: dictator@example.com
        upload_filetypes: jpg jpeg png
        site_unlimited_upload: false
        site_upload_space: 1000
        site_max_upload: 5000
        enabled_themes:
          - p2
        active_plugins:
          - akismet/akismet.php
      """

    When I run `wp plugin install akismet --force`
    And I run `wp theme install p2 --force`
    Then STDOUT should not be empty

    When I run `wp dictator impose network-state.yml`
    Then STDOUT should not be empty

    When I run `wp dictator compare network-state.yml`
    Then STDOUT should be empty

    When I run `wp network-meta get 1 site_name`
    Then STDOUT should be:
      """
      Dictator Network
      """

    When I run `wp network-meta get 1 admin_email`
    Then STDOUT should be:
      """
      dictator@example.com
      """

    When I run `wp network-meta get 1 upload_filetypes`
    Then STDOUT should be:
      """
      jpg jpeg png
      """

    When I run `wp network-meta get 1 upload_space_check_disabled`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp network-meta get 1 blog_upload_space`
    Then STDOUT should be:
      """
      1000
      """

    When I run `wp network-meta get 1 fileupload_maxk`
    Then STDOUT should be:
      """
      5000
      """

    When I run `wp theme list --fields=name,enabled`
    Then STDOUT should be a table containing rows:
      | name     | enabled        |
      | p2       | network        |

    When I run `wp plugin list --fields=name,status`
    Then STDOUT should be a table containing rows:
      | name     | status            |
      | akismet  | active-network    |
