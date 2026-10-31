<?php
declare(strict_types=1);

namespace BioVet\App\Http\Controllers;

use BioVet\App\Http\HttpRequest;
use BioVet\App\Http\HttpResponse;
use BioVet\App\Models\User;
use BioVet\App\Services\TokenVerificationService;
use PDOException;
use RuntimeException;
use Throwable;

final class TokenVerificationController
{
    public function __construct(private readonly TokenVerificationService $verification)
    {
    }

    public function verify(HttpRequest $request): HttpResponse
    {
        if ($request->method === 'OPTIONS') {
            return HttpResponse::empty(200, ['Content-Type' => 'application/json']);
        }

        $token = $this->tokenFromRequest($request);
        if (!$token) {
            return HttpResponse::json(401, [
                'success' => false,
                'valid' => false,
                'mensaje' => 'Token no proporcionado',
                'error' => 'MISSING_TOKEN'
            ], ['Content-Type' => 'application/json']);
        }

        try {
            $result = $this->verification->verify($token);
            if ($result['type'] === 'invalid_token') {
                return HttpResponse::json(401, [
                    'success' => false,
                    'valid' => false,
                    'mensaje' => 'Token inválido o expirado',
                    'error' => 'INVALID_TOKEN'
                ], ['Content-Type' => 'application/json']);
            }
            if ($result['type'] === 'user_not_found') {
                return HttpResponse::json(404, [
                    'success' => false,
                    'valid' => false,
                    'mensaje' => 'Usuario no encontrado en el sistema',
                    'error' => 'USER_NOT_FOUND'
                ], ['Content-Type' => 'application/json']);
            }

            /** @var User $user */
            $user = $result['user'];
            $payload = $result['payload'];
            $remaining = isset($payload['exp']) ? $payload['exp'] - time() : null;
            $remainingHours = $remaining ? round($remaining / 3600, 1) : null;

            return HttpResponse::json(200, [
                'success' => true,
                'valid' => true,
                'mensaje' => 'Token válido',
                'usuario' => $user->verificationRepresentation(),
                'token' => [
                    'expira_en' => $remaining,
                    'expira_en_horas' => $remainingHours,
                    'expira_en_fecha' => isset($payload['exp'])
                        ? date('Y-m-d H:i:s', $payload['exp'])
                        : null,
                    'emitido_en' => isset($payload['iat'])
                        ? date('Y-m-d H:i:s', $payload['iat'])
                        : null
                ]
            ], ['Content-Type' => 'application/json']);
        } catch (PDOException $exception) {
            error_log('BioVet token verification database error: ' . $exception->getMessage());
            return HttpResponse::json(500, [
                'success' => false,
                'valid' => false,
                'mensaje' => 'Error interno del servidor',
                'error' => 'SERVER_ERROR'
            ], ['Content-Type' => 'application/json']);
        } catch (RuntimeException $exception) {
            error_log('BioVet token verification configuration error: ' . $exception->getMessage());
            return HttpResponse::json(500, [
                'success' => false,
                'valid' => false,
                'mensaje' => 'Error interno del servidor',
                'error' => 'SERVER_ERROR'
            ], ['Content-Type' => 'application/json']);
        } catch (Throwable $exception) {
            error_log('BioVet token verification internal error: ' . $exception->getMessage());
            return HttpResponse::json(500, [
                'success' => false,
                'valid' => false,
                'mensaje' => 'Error interno del servidor',
                'error' => 'SERVER_ERROR'
            ], ['Content-Type' => 'application/json']);
        }
    }

    private function tokenFromRequest(HttpRequest $request): ?string
    {
        $authorization = $request->header('Authorization');
        if (is_string($authorization)
            && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return trim($matches[1]);
        }

        $queryToken = $request->query['token'] ?? null;
        return is_string($queryToken) ? $queryToken : null;
    }
}
