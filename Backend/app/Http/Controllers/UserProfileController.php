<?php
declare(strict_types=1);

namespace BioVet\App\Http\Controllers;

use BioVet\App\Http\HttpRequest;
use BioVet\App\Http\HttpResponse;
use BioVet\App\Services\UserProfileService;
use BioVet\App\Security\TokenService;
use PDOException;
use RuntimeException;
use Throwable;

final class UserProfileController
{
    public function __construct(
        private readonly UserProfileService $profiles,
        private readonly TokenService $tokens
    ) {
    }

    public function show(HttpRequest $request): HttpResponse
    {
        $headers = [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-store'
        ];
        $authorization = $request->header('Authorization');
        if (!is_string($authorization)
            || !preg_match('/^Bearer\s+([A-Za-z0-9_.-]+)$/i', trim($authorization), $matches)) {
            return HttpResponse::json(401, [
                'success' => false,
                'mensaje' => 'Se requiere un token Bearer',
                'error' => 'MISSING_TOKEN'
            ], $headers, JSON_UNESCAPED_UNICODE);
        }

        try {
            $payload = $this->tokens->verify($matches[1]);
        } catch (RuntimeException $exception) {
            error_log('BioVet user profile JWT configuration error: ' . $exception->getMessage());
            return $this->serverError($headers);
        } catch (Throwable $exception) {
            error_log('BioVet user profile JWT error: ' . $exception->getMessage());
            return $this->serverError($headers);
        }

        $userId = $payload['user_id'] ?? null;
        if (!is_array($payload)
            || (!is_int($userId) && !(is_string($userId) && ctype_digit($userId)))) {
            return HttpResponse::json(401, [
                'success' => false,
                'mensaje' => 'Token inválido o expirado',
                'error' => 'INVALID_TOKEN'
            ], $headers, JSON_UNESCAPED_UNICODE);
        }

        try {
            $user = $this->profiles->getCurrentUser((int) $userId);
            if ($user === null) {
                return HttpResponse::json(404, [
                    'success' => false,
                    'mensaje' => 'Usuario no encontrado',
                    'error' => 'USER_NOT_FOUND'
                ], $headers, JSON_UNESCAPED_UNICODE);
            }

            return HttpResponse::json(200, [
                'success' => true,
                'usuario' => $user->profileRepresentation()
            ], $headers, JSON_UNESCAPED_UNICODE);
        } catch (PDOException $exception) {
            error_log('BioVet user profile database error: ' . $exception->getMessage());
            return $this->serverError($headers);
        } catch (Throwable $exception) {
            error_log('BioVet user profile internal error: ' . $exception->getMessage());
            return $this->serverError($headers);
        }
    }

    private function serverError(array $headers): HttpResponse
    {
        return HttpResponse::json(500, [
            'success' => false,
            'mensaje' => 'Error interno del servidor',
            'error' => 'SERVER_ERROR'
        ], $headers, JSON_UNESCAPED_UNICODE);
    }
}
