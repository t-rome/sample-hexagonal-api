Feature: List and get products

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
