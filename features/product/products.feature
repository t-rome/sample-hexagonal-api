Feature: Product management

  Background:
    Given a user exists with email "user@test.com" and password "password123"
    And the following products exist:
      | name           | price   | description       |
      | Laptop Pro     | 1499.99 | A powerful laptop |
      | Wireless Mouse | 29.99   |                   |

  Scenario: List all products
    When I send a GET request to "/api/products"
    Then the response status code should be 200
    And the JSON response should have 2 items
    And the response matches the OpenAPI spec

  Scenario: Get a specific product
    When I send a GET request to the product named "Laptop Pro"
    Then the response status code should be 200
    And the JSON response field "name" should be "Laptop Pro"
    And the response matches the OpenAPI spec

  Scenario: Get a non-existent product
    When I send a GET request to "/api/products/99999"
    Then the response status code should be 404
    And the response matches the OpenAPI spec

  Scenario: Creating a product requires authentication
    When I send a POST request to "/api/products" with body:
      """
      {"name": "Keyboard", "price": 89.99}
      """
    Then the response status code should be 401

  Scenario: Create a product
    Given I am authenticated as "user@test.com" with password "password123"
    When I send a POST request to "/api/products" with body:
      """
      {"name": "Keyboard", "description": "Mechanical", "price": 89.99}
      """
    Then the response status code should be 201
    And the JSON response field "name" should be "Keyboard"
    And the JSON response should have a field "id"
    And the response matches the OpenAPI spec

  Scenario: Create a product with invalid data
    Given I am authenticated as "user@test.com" with password "password123"
    When I send a POST request to "/api/products" with body:
      """
      {"name": "", "price": -1}
      """
    Then the response status code should be 422
    And the response matches the OpenAPI spec

  Scenario: Update a product
    Given I am authenticated as "user@test.com" with password "password123"
    When I send a PUT request to the product named "Laptop Pro" with body:
      """
      {"name": "Laptop Updated", "price": 1299.99}
      """
    Then the response status code should be 200
    And the JSON response field "name" should be "Laptop Updated"
    And the response matches the OpenAPI spec

  Scenario: Delete a product
    Given I am authenticated as "user@test.com" with password "password123"
    When I send a DELETE request to the product named "Wireless Mouse"
    Then the response status code should be 204
