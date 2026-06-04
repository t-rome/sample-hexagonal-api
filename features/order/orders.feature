Feature: Order management

  Background:
    Given a user exists with email "user@test.com" and password "password123"
    And the following products exist:
      | name           | price   | description       | stock |
      | Laptop Pro     | 1499.99 | A powerful laptop | 10    |
      | Wireless Mouse | 29.99   |                   | 20    |

  Scenario: List all orders
    Given an order exists for the product "Laptop Pro"
    And I am authenticated as "user@test.com" with password "password123"
    When I send a GET request to "/api/orders"
    Then the response status code should be 200
    And the JSON response should have 1 items
    And the response matches the OpenAPI spec

  Scenario: Get a specific order
    Given an order exists for the product "Laptop Pro"
    And I am authenticated as "user@test.com" with password "password123"
    When I send a GET request to the order
    Then the response status code should be 200
    And the JSON response should have a field "items"
    And the response matches the OpenAPI spec

  Scenario: Get a non-existent order
    Given I am authenticated as "user@test.com" with password "password123"
    When I send a GET request to "/api/orders/99999"
    Then the response status code should be 404
    And the response matches the OpenAPI spec

  Scenario: Placing an order requires authentication
    When I send a POST request to "/api/orders" with body:
      """
      {"items": [{"productId": 1, "quantity": 1}]}
      """
    Then the response status code should be 401

  Scenario: Place an order
    Given I am authenticated as "user@test.com" with password "password123"
    When I place an order with the following items:
      | product        | quantity |
      | Laptop Pro     | 1        |
      | Wireless Mouse | 2        |
    Then the response status code should be 201
    And the JSON response field "status" should be "pending"
    And the response matches the OpenAPI spec

  Scenario: Place an order with insufficient stock
    Given I am authenticated as "user@test.com" with password "password123"
    When I place an order with the following items:
      | product        | quantity |
      | Laptop Pro     | 99       |
    Then the response status code should be 422
    And the response matches the OpenAPI spec

  Scenario: Place an order with unknown product
    Given I am authenticated as "user@test.com" with password "password123"
    When I send a POST request to "/api/orders" with body:
      """
      {"items": [{"productId": 99999, "quantity": 1}]}
      """
    Then the response status code should be 404
    And the response matches the OpenAPI spec

  Scenario: Place an order with invalid data
    Given I am authenticated as "user@test.com" with password "password123"
    When I send a POST request to "/api/orders" with body:
      """
      {"items": []}
      """
    Then the response status code should be 422
    And the response matches the OpenAPI spec

  Scenario: Pay an order
    Given an order exists for the product "Laptop Pro"
    And I am authenticated as "user@test.com" with password "password123"
    When I pay the order
    Then the response status code should be 200
    And the JSON response field "status" should be "confirmed"
    And the response matches the OpenAPI spec

  Scenario: Paying a confirmed order returns conflict
    Given an order exists for the product "Laptop Pro"
    And I am authenticated as "user@test.com" with password "password123"
    When I pay the order
    Then the response status code should be 200
    When I pay the order
    Then the response status code should be 409
    And the response matches the OpenAPI spec

  Scenario: Payment declined returns 402
    Given an order exists for the product "Laptop Pro"
    And the payment gateway will decline
    And I am authenticated as "user@test.com" with password "password123"
    When I pay the order
    Then the response status code should be 402
    And the response matches the OpenAPI spec

  Scenario: Paying an order requires authentication
    Given an order exists for the product "Laptop Pro"
    When I pay the order
    Then the response status code should be 401

  Scenario: Pay a non-existent order
    Given I am authenticated as "user@test.com" with password "password123"
    When I send a PATCH request to "/api/orders/99999/pay"
    Then the response status code should be 404
    And the response matches the OpenAPI spec
