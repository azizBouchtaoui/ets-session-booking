<?php

namespace App\Repository;

use App\Document\Reservation;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class ReservationRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $uow = $dm->getUnitOfWork();
        $classMetadata = $dm->getClassMetadata(Reservation::class);
        parent::__construct($dm, $uow, $classMetadata);
    }

    public function findByUser(string $userId): array
    {
        return $this->findBy(['userId' => $userId]);
    }

    public function findOneByUserAndSession(string $userId, string $sessionId): ?Reservation
    {
        return $this->findOneBy([
            'userId' => $userId,
            'sessionId' => $sessionId,
        ]);
    }

    public function countBySession(string $sessionId): int
    {
        return (int) $this->createQueryBuilder()
            ->field('sessionId')->equals($sessionId)
            ->count()
            ->getQuery()
            ->execute();
    }
}
