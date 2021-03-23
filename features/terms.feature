Feature: Site Terms Region

  Scenario: Impose Terms
    Given a WP install
    And a terms.yml file:
      """
      state: site
      terms:
        category:
          termone:
            name: Term One
            description: The description of term one.
          termtwo:
            name: Term Two
            description: The description of term two.
            parent: termone
      """

    When I run `wp dictator impose terms.yml`
    Then STDOUT should not be empty

    When I run `wp dictator compare terms.yml`
    Then STDOUT should be empty

    When I run `wp term list category --fields=name,description`
    Then STDOUT should be a table containing rows:
      | name           | description                  |
      | Term One       | The description of term one. |
      | Term Two       | The description of term two. |
