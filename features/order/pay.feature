Feature: Pay an order

  Background:
    Given a user exists
    And the following products exist:
      | name           | price   | description       | stock |
      | Laptop Pro     | 1499.99 | A powerful laptop | 10    |
      | Wireless Mouse | 29.99   |                   | 20    |

  Scenario: Pay an order
    Given an order exists for the product "Laptop Pro"
    And I am authenticated as a user
    When I send a PATCH request to "/api/orders/1/pay"
    Then the response status code should be 200
    And the JSON response field "status" should be "confirmed"
    And the response matches the OpenAPI spec

  Scenario: Paying a confirmed order returns conflict
    Given an order exists for the product "Laptop Pro"
    And I am authenticated as a user
    When I send a PATCH request to "/api/orders/1/pay"
    Then the response status code should be 200
    When I send a PATCH request to "/api/orders/1/pay"
    Then the response status code should be 409
    And the response matches the OpenAPI spec

  Scenario: Payment declined returns 402
    Given an order exists for the product "Laptop Pro"
    And the payment gateway will decline
    And I am authenticated as a user
    When I send a PATCH request to "/api/orders/1/pay"
    Then the response status code should be 402
    And the response matches the OpenAPI spec

  Scenario: Paying an order requires authentication
    Given an order exists for the product "Laptop Pro"
    When I send a PATCH request to "/api/orders/1/pay"
    Then the response status code should be 401

  Scenario: Pay a non-existent order
    Given I am authenticated as a user
    When I send a PATCH request to "/api/orders/99999/pay"
    Then the response status code should be 404
    And the response matches the OpenAPI spec
