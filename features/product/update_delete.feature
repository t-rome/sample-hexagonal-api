Feature: Update and delete a product

  Background:
    Given a user exists
    And an admin exists
    And the following products exist:
      | name           | price   | description       |
      | Laptop Pro     | 1499.99 | A powerful laptop |
      | Wireless Mouse | 29.99   |                   |

  Scenario: Updating a product requires admin role
    Given I am authenticated as a user
    When I send a PUT request to "/api/products/1" with body:
      """
      {"name": "Laptop Updated", "price": 1299.99}
      """
    Then the response status code should be 403

  Scenario: Update a product
    Given I am authenticated as an admin
    When I send a PUT request to "/api/products/1" with body:
      """
      {"name": "Laptop Updated", "price": 1299.99}
      """
    Then the response status code should be 200
    And the request body matches the OpenAPI spec
    And the JSON response is:
      """
      {"id": 1, "name": "Laptop Updated", "description": null, "price": 1299.99, "stock": 0, "createdAt": "@any"}
      """
    And the response matches the OpenAPI spec

  Scenario: Deleting a product requires admin role
    Given I am authenticated as a user
    When I send a DELETE request to "/api/products/2"
    Then the response status code should be 403

  Scenario: Delete a product
    Given I am authenticated as an admin
    When I send a DELETE request to "/api/products/2"
    Then the response status code should be 204
