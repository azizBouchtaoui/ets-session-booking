<?php

namespace App\Service;

use App\Document\User;
use App\Exception\ApiException;
use App\Repository\UserRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function register(string $email, string $name, string $plainPassword): User
    {
        if ($this->userRepository->emailExists($email)) {
            throw new ApiException('This email address is already in use.', 409);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $this->dm->persist($user);
        $this->dm->flush();

        return $user;
    }

    public function updateProfile(User $user, ?string $name, ?string $email): User
    {
        if ($name !== null) {
            $user->setName($name);
        }

        if ($email !== null && $email !== $user->getEmail()) {
            if ($this->userRepository->emailExists($email)) {
                throw new ApiException('This email address is already in use.', 409);
            }

            $user->setEmail($email);
        }

        $this->dm->flush();

        return $user;
    }
}
