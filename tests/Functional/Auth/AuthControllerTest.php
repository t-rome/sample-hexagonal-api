<?php

declare(strict_types=1);

namespace App\Tests\Functional\Auth;

use App\Tests\Shared\DatabaseTestCase;
use App\Tests\Shared\Fixture\UserFixture;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends DatabaseTestCase
{
    protected function fixtures(): array
    {
        return [
            static::getContainer()->get(UserFixture::class),
        ];
    }

    public function testLoginReturnsJwtToken(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => UserFixture::USER_EMAIL, 'password' => UserFixture::USER_PASSWORD]),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => UserFixture::USER_EMAIL, 'password' => 'wrongpassword']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRegisterCreatesUser(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'new@test.com', 'password' => 'newpassword123']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('new@test.com', $data['email']);
        $this->assertArrayHasKey('id', $data);
    }

    public function testRegisterFailsWithDuplicateEmail(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => UserFixture::USER_EMAIL, 'password' => 'password123']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testRegisterValidationError(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'not-an-email', 'password' => 'short']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
