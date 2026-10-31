<?php
declare(strict_types=1);

namespace BioVet\App\Security;

interface TokenService
{
    public function issue(array $claims): string;

    public function verify(string $token): ?array;
}
