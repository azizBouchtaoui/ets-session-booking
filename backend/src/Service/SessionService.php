<?php

namespace App\Service;

use App\Document\Session;
use App\Exception\ApiException;
use App\Repository\ReservationRepository;
use App\Repository\SessionRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

class SessionService
{
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly SessionRepository $sessionRepository,
        private readonly ReservationRepository $reservationRepository,
    ) {
    }

    public function create(
        string $language,
        \DateTimeImmutable $scheduledAt,
        string $location,
        int $capacity,
    ): Session {
        $session = new Session();
        $session->setLanguage($language);
        $session->setScheduledAt($scheduledAt);
        $session->setLocation($location);
        $session->setCapacity($capacity);
        $session->initAvailableSpots();

        $this->dm->persist($session);
        $this->dm->flush();

        return $session;
    }

    public function update(
        string $id,
        ?string $language,
        ?\DateTimeImmutable $scheduledAt,
        ?string $location,
        ?int $capacity,
    ): Session {
        $session = $this->findOrFail($id);

        if ($language !== null) {
            $session->setLanguage($language);
        }

        if ($scheduledAt !== null) {
            $session->setScheduledAt($scheduledAt);
        }

        if ($location !== null) {
            $session->setLocation($location);
        }

        if ($capacity !== null && $capacity !== $session->getCapacity()) {
            $diff = $capacity - $session->getCapacity();
            $newAvailable = $session->getAvailableSpots() + $diff;

            if ($newAvailable < 0) {
                throw new ApiException('New capacity is lower than the number of existing reservations.', 409);
            }

            $session->setCapacity($capacity);
            $session->setAvailableSpots($newAvailable);
        }

        $this->dm->flush();

        return $session;
    }

    public function delete(string $id): void
    {
        $session = $this->findOrFail($id);

        if ($this->reservationRepository->countBySession($id) > 0) {
            throw new ApiException('Cannot delete a session with existing reservations.', 409);
        }

        $this->dm->remove($session);
        $this->dm->flush();
    }

    public function findOrFail(string $id): Session
    {
        $session = $this->sessionRepository->find($id);

        if ($session === null) {
            throw new ApiException('Session not found.', 404);
        }

        return $session;
    }
}
