Feature: Update and delete a product

  Background:
    Given a user exists with email "user@test.com" and password "password123"
    And the following products exist:
      | name           | price   | description       |
      | Laptop Pro     | 1499.99 | A powerful laptop |
      | Wireless Mouse | 29.99   |                   |

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
