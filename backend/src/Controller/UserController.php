<?php

namespace App\Controller;

use App\Document\User;
use App\DTO\UpdateUserRequest;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(private readonly UserService $userService)
    {
    }

    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        return $this->json($this->getUser(), 200, [], ['groups' => ['user:read']]);
    }

    #[Route('/me', methods: ['PUT'])]
    public function update(#[MapRequestPayload] UpdateUserRequest $dto): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $user = $this->userService->updateProfile($user, $dto->name, $dto->email);

        return $this->json($user, 200, [], ['groups' => ['user:read']]);
    }
}
