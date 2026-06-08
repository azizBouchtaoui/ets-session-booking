<?php

namespace App\Controller;

use App\Document\User;
use App\DTO\CreateReservationRequest;
use App\Repository\ReservationRepository;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/reservations')]
class ReservationController extends AbstractController
{
    public function __construct(
        private readonly ReservationService $reservationService,
        private readonly ReservationRepository $reservationRepository,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $reservations = $this->reservationRepository->findByUser($user->getId());

        return $this->json($reservations, 200, [], ['groups' => ['reservation:read']]);
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateReservationRequest $dto): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $reservation = $this->reservationService->reserve($user, $dto->sessionId);

        return $this->json($reservation, 201, [], ['groups' => ['reservation:read']]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function cancel(string $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->reservationService->cancel($user, $id);

        return new JsonResponse(null, 204);
    }
}
