<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Product\Infrastructure\Persistence\ProductRecord;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ApiContext implements Context
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private ?string $token = null;

    public function __construct()
    {
        $container = KernelBoot::container();
        $this->client = $container->get('test.client');
        $this->em = $container->get(EntityManagerInterface::class);
    }

    #[BeforeScenario]
    public function reset(): void
    {
        $this->client->restart();
        $this->token = null;
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

    #[When('I send a DELETE request to the product named :name')]
    public function iSendADeleteRequestToTheProductNamed(string $name): void
    {
        $id = $this->resolveProductId($name);
        $this->client->request('DELETE', '/api/products/'.$id, [], [], $this->authHeaders());
    }

    #[Then('the response status code should be :code')]
    public function theResponseStatusCodeShouldBe(int $code): void
    {
        Assert::assertSame($code, $this->client->getResponse()->getStatusCode());
    }

    #[Then('the JSON response should have :count items')]
    public function theJsonResponseShouldHaveItems(int $count): void
    {
        Assert::assertCount($count, $this->getResponseJson());
    }

    #[Then('the JSON response field :field should be :value')]
    public function theJsonResponseFieldShouldBe(string $field, string $value): void
    {
        $data = $this->getResponseJson();
        Assert::assertArrayHasKey($field, $data);
        Assert::assertEquals($value, $data[$field]);
    }

    #[Then('the JSON response should have a field :field')]
    public function theJsonResponseShouldHaveAField(string $field): void
    {
        Assert::assertArrayHasKey($field, $this->getResponseJson());
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
            throw new \RuntimeException("Product not found: \"$name\"");
        }

        return $product->id;
    }
}
