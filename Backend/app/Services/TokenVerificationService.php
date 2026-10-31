<?php
declare(strict_types=1);

namespace BioVet\App\Services;

use BioVet\App\Models\User;
use BioVet\App\Repositories\UserRepository;
use BioVet\App\Security\TokenService;

final class TokenVerificationService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly TokenService $tokens
    ) {
    }

    public function verify(string $token): array
    {
        $payload = $this->tokens->verify($token);
        if ($payload === null || !isset($payload['user_id'])) {
            return ['type' => 'invalid_token'];
        }

        $user = $this->users->findById($payload['user_id']);
        if ($user === null) {
            return ['type' => 'user_not_found'];
        }

        return ['type' => 'valid', 'user' => $user, 'payload' => $payload];
    }
}
