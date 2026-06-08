<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateSessionRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    public string $language = '';

    #[Assert\NotBlank]
    public string $scheduledAt = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public string $location = '';

    #[Assert\Positive]
    public int $capacity = 0;
}
