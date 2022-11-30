Feature: Site / Network Terms Region

  Scenario: Impose Site Terms
    Given a WP install
    And a site-terms.yml file:
      """
      state: site
      terms:
        category:
          term1:
            name: Term One
          term2:
            name: Term Two
      """

    When I run `wp dictator impose site-terms.yml`
    Then STDOUT should not be empty

    When I run `wp dictator compare site-terms.yml`
    Then STDOUT should be empty

    When I run `wp term list category --fields=name,slug`
    Then STDOUT should be a table containing rows:
      | name          | slug          |
      | Uncategorized | uncategorized |
      | Term One      | term1         |
      | Term Two      | term2         |

    When I run `wp dictator export site export.yml`
    Then STDOUT should contain:
    """
    Success: State written to file.
    """

  Scenario: Impose Network Terms
    Given a WP multisite install
    And a network-terms.yml file:
      """
      state: network
      sites:
        :
          terms:
            category:
              term1:
                name: Term One
              term2:
                name: Term Two
      """

    When I run `wp dictator impose network-terms.yml`
    Then STDOUT should not be empty

    When I run `wp dictator compare network-terms.yml`
    Then STDOUT should be empty

    When I run `wp term list category --url=example.com --fields=name,slug`
    Then STDOUT should be a table containing rows:
      | name          | slug          |
      | Term One      | term1         |
      | Term Two      | term2         |
      | Uncategorized | uncategorized |

    When I run `wp dictator export network export.yml`
    Then STDOUT should contain:
    """
    Success: State written to file.
    """

    Scenario: Impose Different Terms For Multiple Network Sites
    Given a WP multisite install
    And a network-terms.yml file:
      """
      state: network
      sites:
        :
          terms:
            category:
              term10:
                name: Term Ten
              term20:
                name: Term Twenty
        enolagay:
          terms:
            category:
              term30:
                name: Term Thirty
              term40:
                name: Term Fourty
      """

    When I run `wp dictator impose network-terms.yml`
    Then STDOUT should not be empty

    When I run `wp dictator compare network-terms.yml`
    Then STDOUT should be empty

    When I run `wp term list category --url=example.com --fields=name,slug`
    Then STDOUT should be a table containing rows:
      | name          | slug          |
      | Term Ten      | term10        |
      | Term Twenty   | term20        |
      | Uncategorized | uncategorized |

    When I run `wp term list category --url=example.com/enolagay --fields=name,slug`
    Then STDOUT should be a table containing rows:
      | name          | slug          |
      | Term Thirty   | term30        |
      | Term Fourty   | term40        |
      | Uncategorized | uncategorized |

    When I run `wp dictator export network export.yml`
    Then STDOUT should contain:
    """
    Success: State written to file.
    """
