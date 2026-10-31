<?php
declare(strict_types=1);

namespace BioVet\App\Models;

final class User
{
    public function __construct(
        public readonly int|string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $rol,
        public readonly ?string $telefono = null,
        public readonly ?string $passwordHash = null,
        public readonly ?string $ultimoAcceso = null,
        public readonly ?string $fechaRegistro = null
    ) {
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            $row['id'],
            (string) $row['name'],
            (string) $row['email'],
            (string) ($row['rol'] ?? ''),
            isset($row['telefono']) ? (string) $row['telefono'] : null,
            isset($row['password']) ? (string) $row['password'] : null,
            isset($row['ultimo_acceso']) ? (string) $row['ultimo_acceso'] : null,
            isset($row['created_at']) ? (string) $row['created_at'] : null
        );
    }

    public function loginRepresentation(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'rol' => $this->rol,
            'telefono' => $this->telefono
        ];
    }

    public function profileRepresentation(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'rol' => $this->rol,
            'telefono' => $this->telefono,
            'ultimo_acceso' => $this->ultimoAcceso,
            'fecha_registro' => $this->fechaRegistro
        ];
    }

    public function verificationRepresentation(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'rol' => $this->rol,
            'ultimo_acceso' => $this->ultimoAcceso
        ];
    }
}
