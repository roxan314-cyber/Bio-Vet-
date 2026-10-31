<?php
declare(strict_types=1);

namespace BioVet\App\Services;

use BioVet\App\Models\User;
use BioVet\App\Repositories\UserRepository;
use BioVet\App\Security\TokenService;
use PDOException;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly TokenService $tokens
    ) {
    }

    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || $user->passwordHash === null
            || !password_verify($password, $user->passwordHash)) {
            return ['type' => 'invalid_credentials'];
        }

        $token = $this->tokens->issue([
            'user_id' => $user->id,
            'email' => $user->email,
            'rol' => $user->rol
        ]);
        $this->users->updateLastLogin($user->id);

        return ['type' => 'success', 'user' => $user, 'token' => $token];
    }

    public function register(object $input): array
    {
        $characterLength = static function (string $value): int {
            $length = preg_match_all('/./us', $value);
            return $length === false ? PHP_INT_MAX : $length;
        };

        $errors = [];
        $nameValue = $input->name ?? null;
        $emailValue = $input->email ?? null;
        $passwordValue = $input->password ?? null;
        $confirmationValue = $input->password_confirmation ?? null;
        $phoneValue = $input->telefono ?? null;

        $name = is_string($nameValue) ? trim($nameValue) : '';
        $email = is_string($emailValue) ? trim($emailValue) : '';
        $password = is_string($passwordValue) ? $passwordValue : '';
        $passwordConfirmation = is_string($confirmationValue) ? $confirmationValue : '';
        $phone = is_string($phoneValue) ? trim($phoneValue) : null;

        if ($name === '' || $characterLength($name) > 100) {
            $errors['name'] = 'El nombre es requerido y debe tener como máximo 100 caracteres';
        }
        if ($email === '' || strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email válido es requerido y debe tener como máximo 100 caracteres';
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors['password'] = 'La contraseña debe tener entre 8 y 72 bytes';
        }
        if ($password !== $passwordConfirmation) {
            $errors['password_confirmation'] = 'Las contraseñas no coinciden';
        }
        if ($phoneValue !== null && !is_string($phoneValue)) {
            $errors['telefono'] = 'El teléfono debe ser texto';
        } elseif ($phone !== null && $characterLength($phone) > 30) {
            $errors['telefono'] = 'El teléfono debe tener como máximo 30 caracteres';
        }

        if ($errors !== []) {
            return ['type' => 'validation_error', 'errors' => $errors];
        }

        if ($this->users->emailExists($email)) {
            return ['type' => 'duplicate_email'];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if (!is_string($passwordHash)) {
            throw new \RuntimeException('Password hashing failed.');
        }

        try {
            $id = $this->users->create(
                $name,
                $email,
                $passwordHash,
                'veterinario',
                $phone === '' ? null : $phone
            );
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                return ['type' => 'duplicate_email'];
            }

            throw $exception;
        }

        return [
            'type' => 'created',
            'user' => ['id' => $id, 'name' => $name, 'email' => $email]
        ];
    }
}
