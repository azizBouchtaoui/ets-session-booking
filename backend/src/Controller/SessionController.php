<?php

namespace App\Controller;

use App\DTO\CreateSessionRequest;
use App\DTO\UpdateSessionRequest;
use App\Exception\ApiException;
use App\Repository\SessionRepository;
use App\Service\SessionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/sessions')]
class SessionController extends AbstractController
{
    public function __construct(
        private readonly SessionService $sessionService,
        private readonly SessionRepository $sessionRepository,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(
        #[MapQueryParameter] ?int $page = 1,
        #[MapQueryParameter] ?int $limit = 10,
    ): JsonResponse {
        $page  = max(1, $page ?? 1);
        $limit = max(1, min($limit ?? 10, 50));

        $result = $this->sessionRepository->findPaginated($page, $limit);

        return $this->json($result, 200, [], ['groups' => ['session:read']]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        return $this->json(
            $this->sessionService->findOrFail($id),
            200,
            [],
            ['groups' => ['session:read']]
        );
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(#[MapRequestPayload] CreateSessionRequest $dto): JsonResponse
    {
        try {
            $scheduledAt = new \DateTimeImmutable($dto->scheduledAt);
        } catch (\Exception) {
            throw new ApiException('Invalid date format for scheduledAt. Use ISO 8601.', 400);
        }

        $session = $this->sessionService->create(
            $dto->language,
            $scheduledAt,
            $dto->location,
            $dto->capacity,
        );

        return $this->json($session, 201, [], ['groups' => ['session:read']]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(string $id, #[MapRequestPayload] UpdateSessionRequest $dto): JsonResponse
    {
        $scheduledAt = null;
        if ($dto->scheduledAt !== null) {
            try {
                $scheduledAt = new \DateTimeImmutable($dto->scheduledAt);
            } catch (\Exception) {
                throw new ApiException('Invalid date format for scheduledAt. Use ISO 8601.', 400);
            }
        }

        $session = $this->sessionService->update(
            $id,
            $dto->language,
            $scheduledAt,
            $dto->location,
            $dto->capacity,
        );

        return $this->json($session, 200, [], ['groups' => ['session:read']]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $id): JsonResponse
    {
        $this->sessionService->delete($id);

        return new JsonResponse(null, 204);
    }
}
