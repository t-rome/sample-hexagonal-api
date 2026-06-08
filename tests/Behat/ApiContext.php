<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Order\Infrastructure\Payment\FakePaymentGateway;
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

    #[Given('I am authenticated as a user')]
    public function iAmAuthenticatedAsUser(): void
    {
        $this->authenticate(DatabaseContext::USER_EMAIL, DatabaseContext::PASSWORD);
    }

    #[Given('I am authenticated as an admin')]
    public function iAmAuthenticatedAsAdmin(): void
    {
        $this->authenticate(DatabaseContext::ADMIN_EMAIL, DatabaseContext::PASSWORD);
    }

    private function authenticate(string $email, string $password): void
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

    #[When('I send a POST request to :url with items:')]
    public function iSendAPostRequestWithItems(string $url, TableNode $table): void
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
            $url,
            [],
            [],
            array_merge(['CONTENT_TYPE' => 'application/json'], $this->authHeaders()),
            json_encode(['items' => $items]),
        );
    }

    #[When('I send a PUT request to :url with body:')]
    public function iSendAPutRequest(string $url, PyStringNode $body): void
    {
        $this->client->request(
            'PUT',
            $url,
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

    #[When('I send a DELETE request to :url')]
    public function iSendADeleteRequest(string $url): void
    {
        $this->client->request('DELETE', $url, [], [], $this->authHeaders());
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

    #[Then('the JSON response is:')]
    public function theJsonResponseIs(PyStringNode $expected): void
    {
        $actual = $this->getResponseJson();
        $expected = json_decode($expected->getRaw(), true, 512, \JSON_THROW_ON_ERROR);
        $this->assertJsonMatches($expected, $actual, 'response');
    }

    private function assertJsonMatches(mixed $expected, mixed $actual, string $path): void
    {
        if ('@any' === $expected) {
            return;
        }

        if (is_array($expected)) {
            if (!is_array($actual)) {
                throw new \RuntimeException("At {$path}: expected array but got ".gettype($actual));
            }

            $missing = array_diff(array_keys($expected), array_keys($actual));
            $extra = array_diff(array_keys($actual), array_keys($expected));

            if ($missing) {
                throw new \RuntimeException("At {$path}: missing keys: ".implode(', ', $missing));
            }
            if ($extra) {
                throw new \RuntimeException("At {$path}: unexpected keys: ".implode(', ', $extra));
            }

            foreach ($expected as $key => $value) {
                $this->assertJsonMatches($value, $actual[$key], "{$path}.{$key}");
            }
        } else {
            if ($actual !== $expected) {
                throw new \RuntimeException(\sprintf('At %s: expected %s but got %s', $path, json_encode($expected), json_encode($actual)));
            }
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
}
