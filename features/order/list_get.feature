Feature: List and get orders

  Background:
    Given a user exists
    And the following products exist:
      | name           | price   | description       | stock |
      | Laptop Pro     | 1499.99 | A powerful laptop | 10    |
      | Wireless Mouse | 29.99   |                   | 20    |

  Scenario: List all orders
    Given an order exists for the product "Laptop Pro"
    And I am authenticated as a user
    When I send a GET request to "/api/orders"
    Then the response status code should be 200
    And the JSON response is:
      """
      [
        {
          "id": 1, "uuid": "@any", "userId": 1, "status": "pending", "totalPrice": 1499.99,
          "items": [{"productId": 1, "quantity": 1, "unitPrice": 1499.99, "totalPrice": 1499.99}],
          "createdAt": "@any"
        }
      ]
      """
    And the response matches the OpenAPI spec

  Scenario: Get a specific order
    Given an order exists for the product "Laptop Pro"
    And I am authenticated as a user
    When I send a GET request to "/api/orders/1"
    Then the response status code should be 200
    And the JSON response is:
      """
      {
        "id": 1, "uuid": "@any", "userId": 1, "status": "pending", "totalPrice": 1499.99,
        "items": [{"productId": 1, "quantity": 1, "unitPrice": 1499.99, "totalPrice": 1499.99}],
        "createdAt": "@any"
      }
      """
    And the response matches the OpenAPI spec

  Scenario: Get a non-existent order
    Given I am authenticated as a user
    When I send a GET request to "/api/orders/99999"
    Then the response status code should be 404
    And the JSON response is:
      """
      {"code": 1001, "error": "Order with id \"99999\" not found."}
      """
    And the response matches the OpenAPI spec
