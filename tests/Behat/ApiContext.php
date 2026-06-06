<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Order\Infrastructure\Payment\FakePaymentGateway;
use App\Order\Infrastructure\Persistence\OrderRecord;
use App\Product\Infrastructure\Persistence\ProductRecord;
use App\Tests\Shared\OpenApiValidator;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ApiContext implements Context
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private OpenApiValidator $openApiValidator;
    private ?string $token = null;

    public function __construct()
    {
        $container = KernelBoot::container();
        $this->client = $container->get('test.client');
        $this->em = $container->get(EntityManagerInterface::class);
        $this->openApiValidator = new OpenApiValidator(\dirname(__DIR__, 2).'/docs/openapi.yaml');
    }

    #[BeforeScenario]
    public function reset(): void
    {
        $this->client->restart();
        $this->token = null;
        FakePaymentGateway::willSucceed();
    }

    #[Given('the payment gateway will decline')]
    public function thePaymentGatewayWillDecline(): void
    {
        FakePaymentGateway::willDecline();
    }

    #[Given('I am authenticated as :email with password :password')]
    public function iAmAuthenticatedAs(string $email, string $password): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password]),
        );
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->token = $data['token'];
        $this->client->restart();
    }

    #[When('I send a GET request to :url')]
    public function iSendAGetRequest(string $url): void
    {
        $this->client->request('GET', $url, [], [], $this->authHeaders());
    }

    #[When('I send a GET request to the product named :name')]
    public function iSendAGetRequestToTheProductNamed(string $name): void
    {
        $id = $this->resolveProductId($name);
        $this->client->request('GET', '/api/products/'.$id, [], [], $this->authHeaders());
    }

    #[When('I send a GET request to the order')]
    public function iSendAGetRequestToTheOrder(): void
    {
        $id = $this->resolveOrderId();
        $this->client->request('GET', '/api/orders/'.$id, [], [], $this->authHeaders());
    }

    #[When('I send a POST request to :url with body:')]
    public function iSendAPostRequest(string $url, PyStringNode $body): void
    {
        $this->client->request(
            'POST',
            $url,
            [],
            [],
            array_merge(['CONTENT_TYPE' => 'application/json'], $this->authHeaders()),
            $body->getRaw(),
        );
    }

    #[When('I send a PUT request to the product named :name with body:')]
    public function iSendAPutRequestToTheProductNamed(string $name, PyStringNode $body): void
    {
        $id = $this->resolveProductId($name);
        $this->client->request(
            'PUT',
            '/api/products/'.$id,
            [],
            [],
            array_merge(['CONTENT_TYPE' => 'application/json'], $this->authHeaders()),
            $body->getRaw(),
        );
    }

    #[When('I send a PATCH request to :url')]
    public function iSendAPatchRequest(string $url): void
    {
        $this->client->request('PATCH', $url, [], [], $this->authHeaders());
    }

    #[When('I send a DELETE request to the product named :name')]
    public function iSendADeleteRequestToTheProductNamed(string $name): void
    {
        $id = $this->resolveProductId($name);
        $this->client->request('DELETE', '/api/products/'.$id, [], [], $this->authHeaders());
    }

    #[When('I place an order with the following items:')]
    public function iPlaceAnOrderWithTheFollowingItems(TableNode $table): void
    {
        $items = [];
        foreach ($table->getColumnsHash() as $row) {
            $items[] = [
                'productId' => $this->resolveProductId($row['product']),
                'quantity' => (int) $row['quantity'],
            ];
        }

        $this->client->request(
            'POST',
            '/api/orders',
            [],
            [],
            array_merge(['CONTENT_TYPE' => 'application/json'], $this->authHeaders()),
            json_encode(['items' => $items]),
        );
    }

    #[When('I pay the order')]
    public function iPayTheOrder(): void
    {
        $id = $this->resolveOrderId();
        $this->client->request('PATCH', '/api/orders/'.$id.'/pay', [], [], $this->authHeaders());
    }

    #[Then('the response status code should be :code')]
    public function theResponseStatusCodeShouldBe(int $code): void
    {
        $actual = $this->client->getResponse()->getStatusCode();
        if ($actual !== $code) {
            throw new \RuntimeException(\sprintf("Expected status %d but got %d.\nResponse body: %s", $code, $actual, $this->client->getResponse()->getContent()));
        }
    }

    #[Then('the request body matches the OpenAPI spec')]
    public function theRequestBodyMatchesTheOpenApiSpec(): void
    {
        $method = $this->client->getRequest()->getMethod();
        $path = $this->client->getRequest()->getPathInfo();
        $content = $this->client->getRequest()->getContent();

        if ('' === $content) {
            return;
        }

        try {
            $body = json_decode($content, null, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return;
        }

        $specPath = $this->openApiValidator->resolveSpecPath($path);

        if (!$this->openApiValidator->hasRequestBodySchema($specPath, $method)) {
            return;
        }

        $this->openApiValidator->assertRequestBody($specPath, $method, $body);
    }

    #[Then('the response matches the OpenAPI spec')]
    public function theResponseMatchesTheOpenApiSpec(): void
    {
        $method = $this->client->getRequest()->getMethod();
        $path = $this->client->getRequest()->getPathInfo();
        $statusCode = $this->client->getResponse()->getStatusCode();

        try {
            $body = json_decode($this->client->getResponse()->getContent(), null, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return;
        }

        $specPath = $this->openApiValidator->resolveSpecPath($path);

        if (!$this->openApiValidator->hasResponseSchema($specPath, $method, $statusCode)) {
            return;
        }

        $this->openApiValidator->assertResponse($specPath, $method, $statusCode, $body);
    }

    #[Then('the JSON response should have :count items')]
    public function theJsonResponseShouldHaveItems(int $count): void
    {
        $actual = count($this->getResponseJson());
        if ($actual !== $count) {
            throw new \RuntimeException(\sprintf('Expected %d items but got %d', $count, $actual));
        }
    }

    #[Then('the JSON response field :field should be :value')]
    public function theJsonResponseFieldShouldBe(string $field, string $value): void
    {
        $data = $this->getResponseJson();
        if (!isset($data[$field])) {
            throw new \RuntimeException(\sprintf('Field "%s" not found in response', $field));
        }
        if ((string) $data[$field] !== $value) {
            throw new \RuntimeException(\sprintf('Expected "%s" to be "%s" but got "%s"', $field, $value, $data[$field]));
        }
    }

    #[Then('the JSON response should have a field :field')]
    public function theJsonResponseShouldHaveAField(string $field): void
    {
        $data = $this->getResponseJson();
        if (!array_key_exists($field, $data)) {
            throw new \RuntimeException(\sprintf('Field "%s" not found in response', $field));
        }
    }

    private function getResponseJson(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }

    private function authHeaders(): array
    {
        return $this->token ? ['HTTP_AUTHORIZATION' => 'Bearer '.$this->token] : [];
    }

    private function resolveProductId(string $name): int
    {
        $this->em->clear();
        $product = $this->em->getRepository(ProductRecord::class)->findOneBy(['name' => $name]);
        if (null === $product) {
            throw new \RuntimeException("Product not found: \"{$name}\"");
        }

        return $product->id;
    }

    private function resolveOrderId(): int
    {
        $this->em->clear();
        $order = $this->em->getRepository(OrderRecord::class)->findOneBy([]);
        if (null === $order) {
            throw new \RuntimeException('No order found in database');
        }

        return $order->id;
    }
}
