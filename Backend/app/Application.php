<?php
declare(strict_types=1);

namespace BioVet\App;

use BioVet\App\Http\Controllers\AuthController;
use BioVet\App\Http\Controllers\HealthController;
use BioVet\App\Http\Controllers\TokenVerificationController;
use BioVet\App\Http\Controllers\UserProfileController;
use BioVet\App\Http\HttpRequest;
use BioVet\App\Http\HttpResponse;
use BioVet\App\Repositories\DatabaseHealthRepository;
use BioVet\App\Repositories\PdoUserRepository;
use BioVet\App\Security\JwtTokenService;
use BioVet\App\Services\AuthService;
use BioVet\App\Services\HealthService;
use BioVet\App\Services\TokenVerificationService;
use BioVet\App\Services\UserProfileService;
use PDO;

final class Application
{
    public function __construct(
        private readonly AuthController $auth,
        private readonly TokenVerificationController $verification,
        private readonly UserProfileController $profile,
        private readonly HealthController $health
    ) {
    }

    public function dispatch(array $route, HttpRequest $request): HttpResponse
    {
        $allowedMethods = $route['php_methods'] ?? null;
        if (is_array($allowedMethods) && !in_array($request->method, $allowedMethods, true)) {
            $policy = $route['php_method_not_allowed'] ?? [];
            return HttpResponse::json(
                (int) ($policy['status'] ?? 405),
                $policy['body'] ?? ['success' => false, 'mensaje' => 'Método no permitido'],
                $policy['headers'] ?? [],
                (int) ($policy['json_flags'] ?? 0)
            );
        }

        return match (($route['controller'] ?? '') . ':' . ($route['action'] ?? '')) {
            'auth:login' => $this->auth->login($request),
            'auth:register' => $this->auth->register($request),
            'token_verification:verify' => $this->verification->verify($request),
            'user_profile:show' => $this->profile->show($request),
            'health:check' => $this->health->check($request),
            default => HttpResponse::json(404, [
                'success' => false,
                'mensaje' => 'Ruta API no encontrada'
            ])
        };
    }

    public static function fromEnvironment(): self
    {
        $connectionFactory = static fn (): PDO => \getDBConnection();
        $users = new PdoUserRepository($connectionFactory);
        $tokens = new JwtTokenService();

        return new self(
            new AuthController(new AuthService($users, $tokens)),
            new TokenVerificationController(new TokenVerificationService($users, $tokens)),
            new UserProfileController(new UserProfileService($users), $tokens),
            new HealthController(new HealthService(
                new DatabaseHealthRepository($connectionFactory)
            ))
        );
    }
}
