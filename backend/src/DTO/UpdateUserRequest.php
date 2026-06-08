<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserRequest
{
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    #[Assert\Length(min: 2, max: 100)]
    public ?string $name = null;
}
