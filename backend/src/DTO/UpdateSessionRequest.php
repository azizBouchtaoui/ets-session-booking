<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateSessionRequest
{
    #[Assert\Length(min: 2, max: 50)]
    public ?string $language = null;

    public ?string $scheduledAt = null;

    #[Assert\Length(min: 2, max: 100)]
    public ?string $location = null;

    #[Assert\Positive]
    public ?int $capacity = null;
}
