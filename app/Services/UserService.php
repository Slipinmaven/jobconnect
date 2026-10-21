<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\UserRepository;

class UserService extends BaseService
{
    private UserRepository $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function register(array $data): ?array
    {
        // Validate
        $this->validateRequired($data, ['email', 'password']);
        $this->validateEmail($data['email']);
        $this->validatePassword($data['password']);

        // Check unique
        $this->validateUnique(
            fn() => $this->userRepo->emailExists($data['email']),
            'email',
            'Email already registered'
        );

        if (!$this->passes()) {
            return ['errors' => $this->getErrors()];
        }

        // Hash password
        $data['password_hash'] = $this->hashPassword($data['password']);
        unset($data['password']);

        // Create user
        $userId = $this->userRepo->create($data);

        return $this->userRepo->findById($userId);
    }
}
