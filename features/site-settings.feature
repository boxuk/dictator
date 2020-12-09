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
        public: false
        posts_per_feed: 20
        allow_comments: true
        allow_pingbacks: false
        notify_comments: false
        notify_moderation: true
        active_theme: p2
        active_plugins:
          - akismet/akismet.php
        show_on_front: page
        page_on_front: 1
        page_for_posts: 2
        timezone: Europe/London
        WPLANG: en_GB
      """

    When I run `wp plugin install akismet --force`
    And I run `wp theme install p2 --force`
    Then STDOUT should not be empty

    When I run `wp language core install en_GB`
    Then STDOUT should not be empty

    When I run `wp dictator impose site-state.yml`
    Then STDOUT should not be empty

    When I run `wp dictator compare site-state.yml`
    Then STDOUT should be empty

    When I run `wp option get blogname`
    Then STDOUT should be:
      """
      Salty WordPress
      """

    When I run `wp option get blog_public`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp option get posts_per_rss`
    Then STDOUT should be:
      """
      20
      """

    When I run `wp option get default_comment_status`
    Then STDOUT should be:
      """
      open
      """

    When I run `wp option get default_ping_status`
    Then STDOUT should be:
      """
      closed
      """

    When I run `wp option get stylesheet`
    Then STDOUT should be:
      """
      p2
      """

    When I run `wp plugin list --fields=name,status`
    Then STDOUT should be a table containing rows:
      | name     | status            |
      | akismet  | active            |

    When I run `wp option get show_on_front`
    Then STDOUT should be:
      """
      page
      """

    When I run `wp option get page_on_front`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp option get page_for_posts`
    Then STDOUT should be:
      """
      2
      """

    When I run `wp option get timezone_string`
    Then STDOUT should be:
      """
      Europe/London
      """

    When I run `wp option get WPLANG`
    Then STDOUT should be:
      """
      en_GB
      """
