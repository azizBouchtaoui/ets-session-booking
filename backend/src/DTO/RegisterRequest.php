<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public string $name = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public string $password = '';
}
