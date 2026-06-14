Feature: List and get products

  Background:
    Given a user exists
    And the following products exist:
      | name           | price   | description       |
      | Laptop Pro     | 1499.99 | A powerful laptop |
      | Wireless Mouse | 29.99   |                   |

  Scenario: List all products
    When I send a GET request to "/api/products"
    Then the response status code should be 200
    And the JSON response is:
      """
      [
        {"id": 1, "name": "Laptop Pro", "description": "A powerful laptop", "price": 1499.99, "stock": 0, "createdAt": "@any"},
        {"id": 2, "name": "Wireless Mouse", "description": null, "price": 29.99, "stock": 0, "createdAt": "@any"}
      ]
      """
    And the response matches the OpenAPI spec

  Scenario: Get a specific product
    When I send a GET request to "/api/products/1"
    Then the response status code should be 200
    And the JSON response is:
      """
      {"id": 1, "name": "Laptop Pro", "description": "A powerful laptop", "price": 1499.99, "stock": 0, "createdAt": "@any"}
      """
    And the response matches the OpenAPI spec

  Scenario: Get a non-existent product
    When I send a GET request to "/api/products/99999"
    Then the response status code should be 404
    And the JSON response is:
      """
      {"code": 2001, "error": "Product with id \"99999\" not found."}
      """
    And the response matches the OpenAPI spec
