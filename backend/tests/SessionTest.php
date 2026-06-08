<?php

namespace App\Tests;

class SessionTest extends ApiTestCase
{
    private string $adminJwt;
    private string $userJwt;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->adminJwt = $this->registerAndLogin('admin@example.com', 'Admin', 'password123');
        $this->makeAdmin('admin@example.com');
        // Re-login to get a token with ROLE_ADMIN in claims
        $this->jsonPost('/api/auth/login', ['email' => 'admin@example.com', 'password' => 'password123']);
        $this->adminJwt = $this->client->getCookieJar()->get('jwt')->getValue();

        // Create regular user
        $this->userJwt = $this->registerAndLogin('user@example.com', 'User', 'password123');
    }

    public function testAdminCanCreateSession(): void
    {
        $data = $this->createSession($this->adminJwt);

        static::assertSame('English', $data['language']);
        static::assertSame('Paris', $data['location']);
        static::assertSame(10, $data['capacity']);
        static::assertSame(10, $data['availableSpots']);
        static::assertArrayHasKey('id', $data);
    }

    public function testRegularUserCannotCreateSession(): void
    {
        $this->jsonPost('/api/sessions', [
            'language'    => 'French',
            'scheduledAt' => '2027-12-01T10:00:00+00:00',
            'location'    => 'Lyon',
            'capacity'    => 5,
        ], $this->userJwt);

        static::assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testGetSessionsList(): void
    {
        $this->createSession($this->adminJwt, ['language' => 'Spanish']);
        $this->createSession($this->adminJwt, ['language' => 'German']);

        $data = $this->jsonGet('/api/sessions', $this->userJwt);

        static::assertSame(200, $this->client->getResponse()->getStatusCode());
        static::assertArrayHasKey('items', $data);
        static::assertArrayHasKey('total', $data);
        static::assertArrayHasKey('pages', $data);
        static::assertCount(2, $data['items']);
        static::assertSame(2, $data['total']);
    }

    public function testAdminCanUpdateSession(): void
    {
        $session = $this->createSession($this->adminJwt, ['capacity' => 20]);

        $this->client->request(
            'PUT',
            '/api/sessions/' . $session['id'],
            [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['location' => 'Berlin', 'capacity' => 30])
        );
        $data = json_decode($this->client->getResponse()->getContent(), true);

        static::assertSame(200, $this->client->getResponse()->getStatusCode());
        static::assertSame('Berlin', $data['location']);
        static::assertSame(30, $data['capacity']);
        // availableSpots grows by the same diff (+10)
        static::assertSame(30, $data['availableSpots']);
    }

    public function testAdminCanDeleteSessionWithoutReservations(): void
    {
        $session = $this->createSession($this->adminJwt);

        $status = $this->jsonDelete('/api/sessions/' . $session['id'], $this->adminJwt);

        static::assertSame(204, $status);
    }
}
