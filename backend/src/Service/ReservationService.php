<?php

namespace App\Service;

use App\Document\Reservation;
use App\Document\User;
use App\Exception\ApiException;
use App\Repository\ReservationRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use App\Document\Session;

class ReservationService
{
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly ReservationRepository $reservationRepository,
        private readonly SessionService $sessionService,
    ) {
    }

    public function reserve(User $user, string $sessionId): Reservation
    {
        $userId = $user->getId();

        if ($this->reservationRepository->findOneByUserAndSession($userId, $sessionId) !== null) {
            throw new ApiException('You already have a reservation for this session.', 409);
        }

        $updated = $this->dm->createQueryBuilder(Session::class)
            ->findAndUpdate()
            ->returnNew(true)
            ->field('id')->equals($sessionId)
            ->field('availableSpots')->gt(0)
            ->field('availableSpots')->inc(-1)
            ->getQuery()
            ->execute();

        if ($updated === null) {
            $this->sessionService->findOrFail($sessionId);
            throw new ApiException('This session is fully booked.', 409);
        }

        $reservation = new Reservation($userId, $sessionId);

        $this->dm->persist($reservation);
        $this->dm->flush();

        return $reservation;
    }

    public function cancel(User $user, string $reservationId): void
    {
        $reservation = $this->reservationRepository->find($reservationId);

        if ($reservation === null) {
            throw new ApiException('Reservation not found.', 404);
        }

        if ($reservation->getUserId() !== $user->getId()) {
            throw new ApiException('You are not allowed to cancel this reservation.', 403);
        }

        $this->dm->createQueryBuilder(Session::class)
            ->updateOne()
            ->field('id')->equals($reservation->getSessionId())
            ->field('availableSpots')->inc(1)
            ->getQuery()
            ->execute();

        $this->dm->remove($reservation);
        $this->dm->flush();
    }
}
