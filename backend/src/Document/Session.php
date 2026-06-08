<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\Document(collection: 'sessions', repositoryClass: \App\Repository\SessionRepository::class)]
class Session
{
    #[ODM\Id]
    #[Groups(['session:read'])]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    #[Groups(['session:read'])]
    private string $language = '';

    #[ODM\Field(type: 'date_immutable')]
    #[Assert\NotBlank]
    #[Assert\GreaterThan('now')]
    #[Groups(['session:read'])]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    #[Groups(['session:read'])]
    private string $location = '';

    #[ODM\Field(type: 'int')]
    #[Assert\NotBlank]
    #[Assert\Range(min: 1)]
    #[Groups(['session:read'])]
    private int $capacity = 0;

    #[ODM\Field(type: 'int')]
    #[Groups(['session:read'])]
    private int $availableSpots = 0;

    #[ODM\Field(type: 'date_immutable')]
    #[Groups(['session:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeImmutable $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getAvailableSpots(): int
    {
        return $this->availableSpots;
    }

    public function setAvailableSpots(int $availableSpots): static
    {
        $this->availableSpots = $availableSpots;

        return $this;
    }

    public function initAvailableSpots(): static
    {
        $this->availableSpots = $this->capacity;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
