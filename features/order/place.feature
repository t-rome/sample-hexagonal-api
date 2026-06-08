Feature: Place an order

  Background:
    Given a user exists
    And the following products exist:
      | name           | price   | description       | stock |
      | Laptop Pro     | 1499.99 | A powerful laptop | 10    |
      | Wireless Mouse | 29.99   |                   | 20    |

  Scenario: Placing an order requires authentication
    When I send a POST request to "/api/orders" with body:
      """
      {"items": [{"productId": 1, "quantity": 1}]}
      """
    Then the response status code should be 401

  Scenario: Place an order
    Given I am authenticated as a user
    When I send a POST request to "/api/orders" with items:
      | product        | quantity |
      | Laptop Pro     | 1        |
      | Wireless Mouse | 2        |
    Then the response status code should be 201
    And the request body matches the OpenAPI spec
    And the JSON response field "status" should be "pending"
    And the response matches the OpenAPI spec

  Scenario: Place an order with insufficient stock
    Given I am authenticated as a user
    When I send a POST request to "/api/orders" with items:
      | product        | quantity |
      | Laptop Pro     | 99       |
    Then the response status code should be 422
    And the response matches the OpenAPI spec

  Scenario: Place an order with unknown product
    Given I am authenticated as a user
    When I send a POST request to "/api/orders" with body:
      """
      {"items": [{"productId": 99999, "quantity": 1}]}
      """
    Then the response status code should be 404
    And the response matches the OpenAPI spec

  Scenario: Place an order with invalid data
    Given I am authenticated as a user
    When I send a POST request to "/api/orders" with body:
      """
      {"items": []}
      """
    Then the response status code should be 422
    And the response matches the OpenAPI spec
