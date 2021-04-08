Feature: Site / Network Users Region

  Scenario: Impose Site Users
    Given a WP install
    And a site-users.yml file:
      """
      state: site
      users:
        adminone:
          display_name: Admin One
          email: adminone@example.com
          role: administrator
        editorone:
          display_name: Editor One
          email: editorone@example.com
          role: editor
      """

    When I run `wp dictator impose site-users.yml`
    Then STDOUT should not be empty

    When I run `wp dictator compare site-users.yml`
    Then STDOUT should be empty

    When I run `wp user list --fields=display_name,user_email,roles`
    Then STDOUT should be a table containing rows:
      | display_name   | user_email            | roles             |
      | Admin One      | adminone@example.com  | administrator     |
      | Editor One     | editorone@example.com | editor            |

    When I run `wp dictator export site export.yml`
    Then STDOUT should contain:
    """
    Success: State written to file.
    """
    And the export.yml file should not contain:
    """
    user_pass:
    """

  Scenario: Impose Network Users
      Given a WP multisite install
      And a network-users.yml file:
        """
        state: network
        users:
          adminone:
            display_name: Admin One
            email: adminone@example.com
          editorone:
            display_name: Editor One
            email: editorone@example.com
        """

      When I run `wp dictator impose network-users.yml`
      Then STDOUT should not be empty

      When I run `wp user list --blog_id=0 --fields=display_name,user_email`
      Then STDOUT should be a table containing rows:
        | display_name   | user_email            |
        | Admin One      | adminone@example.com  |
        | Editor One     | editorone@example.com |

      When I run `wp dictator export network export.yml`
      Then STDOUT should contain:
      """
      Success: State written to file.
      """
      And the export.yml file should not contain:
      """
      user_pass:
      """
