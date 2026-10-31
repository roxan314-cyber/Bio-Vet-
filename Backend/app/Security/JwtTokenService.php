<?php
declare(strict_types=1);

namespace BioVet\App\Security;

final class JwtTokenService implements TokenService
{
    public function __construct(private readonly ?string $secret = null)
    {
    }

    public function issue(array $claims): string
    {
        return \generarJWT($claims, $this->secret);
    }

    public function verify(string $token): ?array
    {
        return \verificarJWT($token, $this->secret);
    }
}
