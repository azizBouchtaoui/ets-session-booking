<?php

namespace App\Controller;

use App\DTO\RegisterRequest;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(private readonly UserService $userService)
    {
    }

    #[Route('/register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterRequest $dto): JsonResponse
    {
        $user = $this->userService->register($dto->email, $dto->name, $dto->password);

        return $this->json($user, 201, [], ['groups' => ['user:read']]);
    }

    /**
     * Handled entirely by json_login + Lexik JWT — this body is never reached.
     */
    #[Route('/login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('Use json_login authenticator.');
    }

    #[Route('/logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $response = new JsonResponse(['message' => 'Logged out.']);
        $response->headers->clearCookie('jwt', '/', null, false, true, 'strict');

        return $response;
    }
}
