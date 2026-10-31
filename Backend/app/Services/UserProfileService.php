<?php
declare(strict_types=1);

namespace BioVet\App\Services;

use BioVet\App\Models\User;
use BioVet\App\Repositories\UserRepository;

final class UserProfileService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function getCurrentUser(int $userId): ?User
    {
        return $this->users->findById($userId);
    }
}
