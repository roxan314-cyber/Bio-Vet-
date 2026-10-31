<?php
declare(strict_types=1);

namespace BioVet\App\Repositories;

use BioVet\App\Models\User;

interface UserRepository
{
    public function findByEmail(string $email): ?User;

    public function findById(int|string $id): ?User;

    public function emailExists(string $email): bool;

    public function create(
        string $name,
        string $email,
        string $passwordHash,
        string $role,
        ?string $phone
    ): int;

    public function updateLastLogin(int|string $id): void;
}
