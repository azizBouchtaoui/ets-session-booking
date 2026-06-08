<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ODM\Document(collection: 'reservations', repositoryClass: \App\Repository\ReservationRepository::class)]
#[ODM\Index(keys: ['sessionId' => 'asc', 'userId' => 'asc'], options: ['unique' => true])]
class Reservation
{
    #[ODM\Id]
    #[Groups(['reservation:read'])]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    #[Groups(['reservation:read'])]
    private string $userId = '';

    #[ODM\Field(type: 'string')]
    #[Groups(['reservation:read'])]
    private string $sessionId = '';

    #[ODM\Field(type: 'date_immutable')]
    #[Groups(['reservation:read'])]
    private \DateTimeImmutable $reservedAt;

    public function __construct(string $userId, string $sessionId)
    {
        $this->userId = $userId;
        $this->sessionId = $sessionId;
        $this->reservedAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getReservedAt(): \DateTimeImmutable
    {
        return $this->reservedAt;
    }
}
