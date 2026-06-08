<?php

namespace App\Tests;

use App\Document\Session;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected DocumentManager $dm;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        /** @var DocumentManager $dm */
        $dm = static::getContainer()->get(DocumentManager::class);
        $this->dm = $dm;

        $this->purgeDatabase();
    }

    protected function tearDown(): void
    {
        $this->purgeDatabase();
        parent::tearDown();
    }

    private function purgeDatabase(): void
    {
        $this->dm->getDocumentCollection(User::class)->drop();
        $this->dm->getDocumentCollection(Session::class)->drop();
        $this->dm->getDocumentCollection(\App\Document\Reservation::class)->drop();
        $this->dm->clear();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function jsonPost(string $uri, array $payload, ?string $cookieJwt = null): array
    {
        if ($cookieJwt !== null) {
            $this->client->getCookieJar()->set(
                new \Symfony\Component\BrowserKit\Cookie('jwt', $cookieJwt)
            );
        }

        $this->client->request(
            'POST',
            $uri,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        return $this->decodeResponse();
    }

    protected function jsonGet(string $uri, ?string $cookieJwt = null): array
    {
        if ($cookieJwt !== null) {
            $this->client->getCookieJar()->set(
                new \Symfony\Component\BrowserKit\Cookie('jwt', $cookieJwt)
            );
        }

        $this->client->request('GET', $uri, [], [], []);

        return $this->decodeResponse();
    }

    protected function jsonDelete(string $uri, ?string $cookieJwt = null): int
    {
        if ($cookieJwt !== null) {
            $this->client->getCookieJar()->set(
                new \Symfony\Component\BrowserKit\Cookie('jwt', $cookieJwt)
            );
        }

        $this->client->request('DELETE', $uri);

        return $this->client->getResponse()->getStatusCode();
    }

    /**
     * Register a user and return their JWT cookie value.
     */
    protected function registerAndLogin(string $email, string $name, string $password): string
    {
        $this->jsonPost('/api/auth/register', [
            'email'    => $email,
            'name'     => $name,
            'password' => $password,
        ]);

        $this->jsonPost('/api/auth/login', [
            'email'    => $email,
            'password' => $password,
        ]);

        $cookie = $this->client->getCookieJar()->get('jwt');
        static::assertNotNull($cookie, 'JWT cookie must be set after login');

        return $cookie->getValue();
    }

    /**
     * Give ROLE_ADMIN to a user identified by email, directly in MongoDB.
     */
    protected function makeAdmin(string $email): void
    {
        $this->dm->getDocumentCollection(User::class)->updateOne(
            ['email' => $email],
            ['$set' => ['roles' => ['ROLE_ADMIN']]]
        );
        $this->dm->clear();
    }

    /**
     * Create a session directly through the API (requires admin JWT).
     */
    protected function createSession(string $jwt, array $overrides = []): array
    {
        $payload = array_merge([
            'language'    => 'English',
            'scheduledAt' => '2027-12-01T10:00:00+00:00',
            'location'    => 'Paris',
            'capacity'    => 10,
        ], $overrides);

        $data = $this->jsonPost('/api/sessions', $payload, $jwt);
        static::assertSame(201, $this->client->getResponse()->getStatusCode());

        return $data;
    }

    private function decodeResponse(): array
    {
        $content = $this->client->getResponse()->getContent();

        return $content ? (json_decode($content, true) ?? []) : [];
    }
}
