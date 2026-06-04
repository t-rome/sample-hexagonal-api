<?php

declare(strict_types=1);

namespace App\Tests\Functional\Order;

use App\Order\Infrastructure\Persistence\OrderRecord;
use App\Product\Infrastructure\Persistence\ProductRecord;
use App\Tests\Shared\DatabaseTestCase;
use App\Tests\Shared\Fixture\OrderFixture;
use App\Tests\Shared\Fixture\ProductFixture;
use App\Tests\Shared\Fixture\UserFixture;
use Symfony\Component\HttpFoundation\Response;

class OrderControllerTest extends DatabaseTestCase
{
    protected function fixtures(): array
    {
        return [
            static::getContainer()->get(UserFixture::class),
            new ProductFixture(),
            new OrderFixture(),
        ];
    }

    private function authHeaders(): array
    {
        $token = $this->getJwtToken(UserFixture::USER_EMAIL, UserFixture::USER_PASSWORD);

        return ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token];
    }

    public function testListReturnsAllOrders(): void
    {
        $this->client->request('GET', '/api/orders', [], [], $this->authHeaders());

        $this->assertResponseIsSuccessful();
        $this->assertMatchesOpenApiSpec('/api/orders', 'get', 200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $data);
    }

    public function testGetReturnsOrder(): void
    {
        $order = $this->em->getRepository(OrderRecord::class)->findOneBy([]);

        $this->client->request('GET', '/api/orders/'.$order->id, [], [], $this->authHeaders());

        $this->assertResponseIsSuccessful();
        $this->assertMatchesOpenApiSpec('/api/orders/{id}', 'get', 200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $data['items']);
    }

    public function testGetReturns404ForUnknownOrder(): void
    {
        $this->client->request('GET', '/api/orders/99999', [], [], $this->authHeaders());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertMatchesOpenApiSpec('/api/orders/{id}', 'get', 404);
    }

    public function testPlaceOrderRequiresAuth(): void
    {
        $this->client->request(
            'POST',
            '/api/orders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['items' => [['productId' => 1, 'quantity' => 1]]]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testPlaceOrder(): void
    {
        $laptop = $this->em->getRepository(ProductRecord::class)->findOneBy(['name' => 'Laptop Pro']);
        $mouse = $this->em->getRepository(ProductRecord::class)->findOneBy(['name' => 'Wireless Mouse']);

        $this->client->request(
            'POST',
            '/api/orders',
            [],
            [],
            $this->authHeaders(),
            json_encode([
                'items' => [
                    ['productId' => $laptop->id, 'quantity' => 1],
                    ['productId' => $mouse->id, 'quantity' => 2],
                ],
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertMatchesOpenApiSpec('/api/orders', 'post', 201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $data['items']);
        $this->assertSame('pending', $data['status']);
        $this->assertEqualsWithDelta(1499.99 + 2 * 29.99, $data['totalPrice'], 0.001);
    }

    public function testPlaceOrderReturns404ForUnknownProduct(): void
    {
        $this->client->request(
            'POST',
            '/api/orders',
            [],
            [],
            $this->authHeaders(),
            json_encode(['items' => [['productId' => 99999, 'quantity' => 1]]]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertMatchesOpenApiSpec('/api/orders', 'post', 404);
    }

    public function testPlaceOrderValidationError(): void
    {
        $this->client->request(
            'POST',
            '/api/orders',
            [],
            [],
            $this->authHeaders(),
            json_encode(['items' => []]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertMatchesOpenApiSpec('/api/orders', 'post', 422);
    }
}
