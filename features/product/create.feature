Feature: Create a product

  Background:
    Given a user exists
    And an admin exists
    And the following products exist:
      | name           | price   | description       |
      | Laptop Pro     | 1499.99 | A powerful laptop |
      | Wireless Mouse | 29.99   |                   |

  Scenario: Creating a product requires authentication
    When I send a POST request to "/api/products" with body:
      """
      {"name": "Keyboard", "price": 89.99}
      """
    Then the response status code should be 401

  Scenario: Creating a product requires admin role
    Given I am authenticated as a user
    When I send a POST request to "/api/products" with body:
      """
      {"name": "Keyboard", "price": 89.99}
      """
    Then the response status code should be 403

  Scenario: Create a product
    Given I am authenticated as an admin
    When I send a POST request to "/api/products" with body:
      """
      {"name": "Keyboard", "description": "Mechanical", "price": 89.99}
      """
    Then the response status code should be 201
    And the request body matches the OpenAPI spec
    And the JSON response field "name" should be "Keyboard"
    And the JSON response should have a field "id"
    And the response matches the OpenAPI spec

  Scenario: Create a product with invalid data
    Given I am authenticated as an admin
    When I send a POST request to "/api/products" with body:
      """
      {"name": "", "price": -1}
      """
    Then the response status code should be 422
    And the response matches the OpenAPI spec
