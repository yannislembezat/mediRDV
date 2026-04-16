<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\DataFixtures\AppFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        self::ensureKernelShutdown();
        self::ensureJwtKeyPair();
        $this->client = static::createClient();
        $this->resetDatabase();
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>
     */
    protected function jsonRequest(
        string $method,
        string $uri,
        array $payload = [],
        array $headers = [],
    ): array {
        $this->client->jsonRequest($method, $uri, $payload, $headers);

        return $this->decodeResponse();
    }

    protected function authenticatePatient(string $email = 'ahmed.benali@example.fr'): string
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->entityManager()->getRepository(\App\Entity\User::class)->findOneBy(['email' => $email]);

        self::assertInstanceOf(\App\Entity\User::class, $user);

        return static::getContainer()->get(JWTTokenManagerInterface::class)->create($user);
    }

    protected function authHeaders(?string $token = null): array
    {
        if ($token === null) {
            return [];
        }

        return [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
        ];
    }

    protected function decodeResponse(): array
    {
        $content = $this->client->getResponse()->getContent();

        self::assertNotFalse($content, 'The response body should not be false.');

        /** @var array<string, mixed> $payload */
        $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return $payload;
    }

    protected function assertStatusCode(int $expectedStatusCode): void
    {
        $actualStatusCode = $this->client->getResponse()->getStatusCode();
        $content = $this->client->getResponse()->getContent();

        self::assertSame(
            $expectedStatusCode,
            $actualStatusCode,
            sprintf('Unexpected response body: %s', $content !== false ? $content : '[false]'),
        );
    }

    protected function resetDatabase(): void
    {
        $entityManager = $this->entityManager();
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);

        if ($metadata !== []) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }

        static::getContainer()->get(AppFixtures::class)->load($entityManager);
        $entityManager->clear();
    }

    protected function entityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private static function ensureJwtKeyPair(): void
    {
        $jwtDirectory = dirname(__DIR__, 2).'/config/jwt';
        $privateKeyPath = $jwtDirectory.'/private.pem';
        $publicKeyPath = $jwtDirectory.'/public.pem';

        if (is_file($privateKeyPath) && is_file($publicKeyPath)) {
            return;
        }

        if (!extension_loaded('openssl')) {
            throw new \RuntimeException('The OpenSSL extension is required to generate JWT keys for tests.');
        }

        if (!is_dir($jwtDirectory) && !mkdir($jwtDirectory, 0775, true) && !is_dir($jwtDirectory)) {
            throw new \RuntimeException('Unable to create the JWT key directory for tests.');
        }

        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($resource === false) {
            throw new \RuntimeException('Unable to generate the JWT private key resource for tests.');
        }

        $privateKey = null;
        $details = openssl_pkey_get_details($resource);

        if ($details === false || !isset($details['key']) || !openssl_pkey_export($resource, $privateKey, '')) {
            throw new \RuntimeException('Unable to export the JWT keypair for tests.');
        }

        file_put_contents($privateKeyPath, $privateKey);
        file_put_contents($publicKeyPath, $details['key']);
    }
}
