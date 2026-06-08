<?php

namespace App\Tests;

class ReservationTest extends ApiTestCase
{
    private string $adminJwt;
    private string $userJwt;
    private array $session;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin
        $this->adminJwt = $this->registerAndLogin('admin@example.com', 'Admin', 'password123');
        $this->makeAdmin('admin@example.com');
        $this->jsonPost('/api/auth/login', ['email' => 'admin@example.com', 'password' => 'password123']);
        $this->adminJwt = $this->client->getCookieJar()->get('jwt')->getValue();

        // Regular user
        $this->userJwt = $this->registerAndLogin('user@example.com', 'User', 'password123');

        // Session with 2 spots for limit tests
        $this->session = $this->createSession($this->adminJwt, ['capacity' => 2]);
    }

    public function testUserCanReserveSession(): void
    {
        $data = $this->jsonPost('/api/reservations', [
            'sessionId' => $this->session['id'],
        ], $this->userJwt);

        static::assertSame(201, $this->client->getResponse()->getStatusCode());
        static::assertSame($this->session['id'], $data['sessionId']);
        static::assertArrayHasKey('id', $data);

        // availableSpots must be decremented
        $sessionData = $this->jsonGet('/api/sessions/' . $this->session['id'], $this->userJwt);
        static::assertSame(1, $sessionData['availableSpots']);
    }

    public function testDoubleReservationIsRejected(): void
    {
        $payload = ['sessionId' => $this->session['id']];

        $this->jsonPost('/api/reservations', $payload, $this->userJwt);
        $data = $this->jsonPost('/api/reservations', $payload, $this->userJwt);

        static::assertSame(409, $this->client->getResponse()->getStatusCode());
        static::assertArrayHasKey('message', $data);
    }

    public function testFullyBookedSessionRejectsReservation(): void
    {
        // Fill all 2 spots with distinct users
        $user2Jwt = $this->registerAndLogin('user2@example.com', 'User2', 'password123');
        $user3Jwt = $this->registerAndLogin('user3@example.com', 'User3', 'password123');

        $this->jsonPost('/api/reservations', ['sessionId' => $this->session['id']], $user2Jwt);
        $this->jsonPost('/api/reservations', ['sessionId' => $this->session['id']], $user3Jwt);

        // Third user
        $user4Jwt = $this->registerAndLogin('user4@example.com', 'User4', 'password123');
        $data = $this->jsonPost('/api/reservations', ['sessionId' => $this->session['id']], $user4Jwt);

        static::assertSame(409, $this->client->getResponse()->getStatusCode());
        static::assertArrayHasKey('message', $data);
    }

    public function testUserCanCancelReservation(): void
    {
        $data = $this->jsonPost('/api/reservations', [
            'sessionId' => $this->session['id'],
        ], $this->userJwt);

        $reservationId = $data['id'];

        $status = $this->jsonDelete('/api/reservations/' . $reservationId, $this->userJwt);
        static::assertSame(204, $status);

        // availableSpots must be restored
        $sessionData = $this->jsonGet('/api/sessions/' . $this->session['id'], $this->userJwt);
        static::assertSame(2, $sessionData['availableSpots']);
    }

    public function testCannotCancelAnotherUsersReservation(): void
    {
        $data = $this->jsonPost('/api/reservations', [
            'sessionId' => $this->session['id'],
        ], $this->userJwt);

        $reservationId = $data['id'];

        // Other user tries to cancel
        $otherJwt = $this->registerAndLogin('other@example.com', 'Other', 'password123');
        $this->jsonDelete('/api/reservations/' . $reservationId, $otherJwt);

        static::assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testCannotDeleteSessionWithActiveReservations(): void
    {
        $this->jsonPost('/api/reservations', [
            'sessionId' => $this->session['id'],
        ], $this->userJwt);

        $this->jsonDelete('/api/sessions/' . $this->session['id'], $this->adminJwt);

        static::assertSame(409, $this->client->getResponse()->getStatusCode());
    }
}
