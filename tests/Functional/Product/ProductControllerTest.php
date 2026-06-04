<?php

declare(strict_types=1);

namespace App\Tests\Functional\Product;

use App\Product\Infrastructure\Persistence\ProductRecord;
use App\Tests\Shared\DatabaseTestCase;
use App\Tests\Shared\Fixture\ProductFixture;
use App\Tests\Shared\Fixture\UserFixture;
use Symfony\Component\HttpFoundation\Response;

class ProductControllerTest extends DatabaseTestCase
{
    protected function fixtures(): array
    {
        return [
            static::getContainer()->get(UserFixture::class),
            new ProductFixture(),
        ];
    }

    public function testListReturnsAllProducts(): void
    {
        $this->client->request('GET', '/api/products');

        $this->assertResponseIsSuccessful();
        $this->assertMatchesOpenApiSpec('/api/products', 'get', 200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testGetReturnsProduct(): void
    {
        $laptop = $this->findProductByName('Laptop Pro');

        $this->client->request('GET', '/api/products/'.$laptop->id);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesOpenApiSpec('/api/products/{id}', 'get', 200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Laptop Pro', $data['name']);
        $this->assertSame(1499.99, $data['price']);
    }

    public function testGetReturns404ForUnknownProduct(): void
    {
        $this->client->request('GET', '/api/products/99999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertMatchesOpenApiSpec('/api/products/{id}', 'get', 404);
    }

    public function testCreateRequiresAuth(): void
    {
        $this->client->request(
            'POST',
            '/api/products',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Keyboard', 'price' => 89.99]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateProduct(): void
    {
        $token = $this->getJwtToken(UserFixture::USER_EMAIL, UserFixture::USER_PASSWORD);

        $this->client->request(
            'POST',
            '/api/products',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token],
            json_encode(['name' => 'Keyboard', 'description' => 'Mechanical', 'price' => 89.99]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertMatchesOpenApiSpec('/api/products', 'post', 201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Keyboard', $data['name']);
        $this->assertArrayHasKey('id', $data);
    }

    public function testCreateValidationError(): void
    {
        $token = $this->getJwtToken(UserFixture::USER_EMAIL, UserFixture::USER_PASSWORD);

        $this->client->request(
            'POST',
            '/api/products',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token],
            json_encode(['name' => '', 'price' => -1]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertMatchesOpenApiSpec('/api/products', 'post', 422);
    }

    public function testUpdateProduct(): void
    {
        $token = $this->getJwtToken(UserFixture::USER_EMAIL, UserFixture::USER_PASSWORD);
        $laptop = $this->findProductByName('Laptop Pro');

        $this->client->request(
            'PUT',
            '/api/products/'.$laptop->id,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token],
            json_encode(['name' => 'Laptop Updated', 'price' => 1299.99]),
        );

        $this->assertResponseIsSuccessful();
        $this->assertMatchesOpenApiSpec('/api/products/{id}', 'put', 200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Laptop Updated', $data['name']);
    }

    public function testDeleteProduct(): void
    {
        $token = $this->getJwtToken(UserFixture::USER_EMAIL, UserFixture::USER_PASSWORD);
        $mouse = $this->findProductByName('Wireless Mouse');

        $this->client->request(
            'DELETE',
            '/api/products/'.$mouse->id,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    private function findProductByName(string $name): ProductRecord
    {
        return $this->em->getRepository(ProductRecord::class)->findOneBy(['name' => $name]);
    }
}
