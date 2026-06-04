<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class DatabaseTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;
    private OpenApiValidator $openApi;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->openApi = new OpenApiValidator(\dirname(__DIR__, 2).'/docs/openapi.yaml');

        $this->loadFixtures($this->fixtures());
    }

    /** @return Fixture[] */
    protected function fixtures(): array
    {
        return [];
    }

    /** @param Fixture[] $fixtures */
    private function loadFixtures(array $fixtures): void
    {
        $connection = $this->em->getConnection();
        $connection->executeStatement('SET session_replication_role = replica');

        $executor = new ORMExecutor($this->em, new ORMPurger($this->em));
        $executor->execute($fixtures, false);

        $connection->executeStatement('SET session_replication_role = DEFAULT');
    }

    protected function assertMatchesOpenApiSpec(string $path, string $method, int $statusCode): void
    {
        try {
            $body = json_decode($this->client->getResponse()->getContent(), null, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return; // non-JSON response body — nothing to validate against the schema
        }

        $this->openApi->assertResponse($path, $method, $statusCode, $body);
    }

    protected function getJwtToken(string $email, string $password): string
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

        return $data['token'];
    }
}
