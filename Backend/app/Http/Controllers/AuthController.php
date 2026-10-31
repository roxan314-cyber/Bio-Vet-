<?php
declare(strict_types=1);

namespace BioVet\App\Http\Controllers;

use BioVet\App\Http\HttpRequest;
use BioVet\App\Http\HttpResponse;
use BioVet\App\Models\User;
use BioVet\App\Services\AuthService;
use PDOException;
use Throwable;

final class AuthController
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function login(HttpRequest $request): HttpResponse
    {
        $input = json_decode($request->body === false ? '' : $request->body, true);
        if (!is_array($input)
            || !is_string($input['email'] ?? null)
            || !is_string($input['password'] ?? null)) {
            return HttpResponse::json(400, [
                'success' => false,
                'mensaje' => 'Email y contraseña son requeridos'
            ], ['Content-Type' => 'application/json']);
        }

        try {
            $result = $this->auth->login(trim($input['email']), $input['password']);
            if ($result['type'] === 'invalid_credentials') {
                return HttpResponse::json(401, [
                    'success' => false,
                    'mensaje' => 'Credenciales inválidas'
                ], ['Content-Type' => 'application/json']);
            }

            /** @var User $user */
            $user = $result['user'];
            return HttpResponse::json(200, [
                'success' => true,
                'mensaje' => 'Inicio de sesión exitoso',
                'token' => $result['token'],
                'usuario' => $user->loginRepresentation()
            ], ['Content-Type' => 'application/json']);
        } catch (PDOException $exception) {
            error_log('BioVet login database error: ' . $exception->getMessage());
            return HttpResponse::json(500, [
                'success' => false,
                'mensaje' => 'Error interno del servidor'
            ], ['Content-Type' => 'application/json']);
        } catch (Throwable $exception) {
            error_log('BioVet login internal error: ' . $exception->getMessage());
            return HttpResponse::json(500, [
                'success' => false,
                'mensaje' => 'Error interno del servidor'
            ], ['Content-Type' => 'application/json']);
        }
    }

    public function register(HttpRequest $request): HttpResponse
    {
        $headers = [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-store'
        ];
        if ($request->body === false) {
            return HttpResponse::json(400, [
                'success' => false,
                'errors' => ['request' => 'No se pudo leer el cuerpo de la solicitud']
            ], $headers, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
        if (strlen($request->body) > 65536) {
            return HttpResponse::json(413, [
                'success' => false,
                'mensaje' => 'La solicitud excede el tamaño permitido'
            ], $headers, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }

        $input = json_decode($request->body);
        if (json_last_error() !== JSON_ERROR_NONE || !is_object($input)) {
            return HttpResponse::json(400, [
                'success' => false,
                'errors' => ['request' => 'El cuerpo debe ser un objeto JSON válido']
            ], $headers, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }

        try {
            $result = $this->auth->register($input);
            if ($result['type'] === 'validation_error') {
                return HttpResponse::json(400, [
                    'success' => false,
                    'errors' => $result['errors']
                ], $headers, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            }
            if ($result['type'] === 'duplicate_email') {
                return HttpResponse::json(409, [
                    'success' => false,
                    'mensaje' => 'El email ya está registrado'
                ], $headers, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            }

            return HttpResponse::json(201, [
                'success' => true,
                'mensaje' => 'Usuario registrado exitosamente',
                'usuario' => $result['user']
            ], $headers, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (PDOException $exception) {
            error_log('BioVet registration database error: ' . $exception->getMessage());

            return HttpResponse::json(500, [
                'success' => false,
                'mensaje' => 'Error interno del servidor'
            ], $headers, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (Throwable $exception) {
            error_log('BioVet registration internal error: ' . $exception->getMessage());

            return HttpResponse::json(500, [
                'success' => false,
                'mensaje' => 'Error interno del servidor'
            ], $headers, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
    }
}
