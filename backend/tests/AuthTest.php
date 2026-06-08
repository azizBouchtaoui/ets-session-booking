<?php

namespace App\Tests;

class AuthTest extends ApiTestCase
{
    public function testRegisterReturnsCreatedUser(): void
    {
        $data = $this->jsonPost('/api/auth/register', [
            'email'    => 'user@example.com',
            'name'     => 'Test User',
            'password' => 'password123',
        ]);

        static::assertSame(201, $this->client->getResponse()->getStatusCode());
        static::assertSame('user@example.com', $data['email']);
        static::assertSame('Test User', $data['name']);
        static::assertContains('ROLE_USER', $data['roles']);
        static::assertArrayHasKey('id', $data);
        static::assertArrayNotHasKey('password', $data);
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $payload = ['email' => 'dup@example.com', 'name' => 'User', 'password' => 'password123'];

        $this->jsonPost('/api/auth/register', $payload);
        $data = $this->jsonPost('/api/auth/register', $payload);

        static::assertSame(409, $this->client->getResponse()->getStatusCode());
        static::assertArrayHasKey('message', $data);
    }

    public function testRegisterValidatesPayload(): void
    {
        $data = $this->jsonPost('/api/auth/register', [
            'email'    => 'not-an-email',
            'name'     => 'A',
            'password' => 'short',
        ]);

        static::assertSame(422, $this->client->getResponse()->getStatusCode());
        static::assertArrayHasKey('errors', $data);
        static::assertArrayHasKey('email', $data['errors']);
        static::assertArrayHasKey('name', $data['errors']);
        static::assertArrayHasKey('password', $data['errors']);
    }

    public function testLoginReturnsCookieJwt(): void
    {
        $this->jsonPost('/api/auth/register', [
            'email'    => 'login@example.com',
            'name'     => 'Login User',
            'password' => 'password123',
        ]);

        $this->jsonPost('/api/auth/login', [
            'email'    => 'login@example.com',
            'password' => 'password123',
        ]);

        static::assertSame(204, $this->client->getResponse()->getStatusCode());

        $cookie = $this->client->getCookieJar()->get('jwt');
        static::assertNotNull($cookie, 'JWT cookie must be present');
        static::assertNotEmpty($cookie->getValue());
    }

    public function testLoginWithWrongPasswordReturns401(): void
    {
        $this->jsonPost('/api/auth/register', [
            'email'    => 'badpwd@example.com',
            'name'     => 'User',
            'password' => 'password123',
        ]);

        $this->jsonPost('/api/auth/login', [
            'email'    => 'badpwd@example.com',
            'password' => 'wrongpassword',
        ]);

        static::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testProtectedEndpointRequiresJwt(): void
    {
        $this->client->getCookieJar()->clear();
        $this->jsonGet('/api/users/me');

        static::assertSame(401, $this->client->getResponse()->getStatusCode());
    }
}
