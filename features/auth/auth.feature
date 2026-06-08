Feature: Authentication

  Background:
    Given a user exists

  Scenario: Login returns a JWT token
    When I send a POST request to "/api/auth/login" with body:
      """
      {"email": "user@test.com", "password": "password123"}
      """
    Then the response status code should be 200
    And the request body matches the OpenAPI spec
    And the JSON response is:
      """
      {"token": "@any"}
      """
    And the response matches the OpenAPI spec

  Scenario: Login fails with wrong password
    When I send a POST request to "/api/auth/login" with body:
      """
      {"email": "user@test.com", "password": "wrongpassword"}
      """
    Then the response status code should be 401

  Scenario: Register creates a new user
    When I send a POST request to "/api/auth/register" with body:
      """
      {"email": "new@test.com", "password": "newpassword123"}
      """
    Then the response status code should be 201
    And the request body matches the OpenAPI spec
    And the JSON response is:
      """
      {"id": 2, "email": "new@test.com"}
      """
    And the response matches the OpenAPI spec

  Scenario: Register fails with duplicate email
    When I send a POST request to "/api/auth/register" with body:
      """
      {"email": "user@test.com", "password": "password123"}
      """
    Then the response status code should be 409

  Scenario: Register fails with invalid data
    When I send a POST request to "/api/auth/register" with body:
      """
      {"email": "not-an-email", "password": "short"}
      """
    Then the response status code should be 422
    And the response matches the OpenAPI spec
