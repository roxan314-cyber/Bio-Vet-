<?php
declare(strict_types=1);

namespace BioVet\App\Repositories;

use BioVet\App\Models\User;
use Closure;
use PDO;

final class PdoUserRepository implements UserRepository
{
    private Closure $connectionFactory;
    private ?PDO $connection = null;

    public function __construct(callable $connectionFactory)
    {
        $this->connectionFactory = Closure::fromCallable($connectionFactory);
    }

    private function connection(): PDO
    {
        if ($this->connection === null) {
            $connection = ($this->connectionFactory)();
            if (!$connection instanceof PDO) {
                throw new \LogicException('The database connection factory must return PDO.');
            }
            $this->connection = $connection;
        }

        if (!$this->connection instanceof PDO) {
            throw new \LogicException('The database connection factory must return PDO.');
        }

        return $this->connection;
    }

    public function findByEmail(string $email): ?User
    {
        $statement = $this->connection()->prepare(
            'SELECT id, name, email, password, rol, telefono FROM users WHERE email = ?'
        );
        $statement->execute([$email]);
        $row = $statement->fetch();

        return is_array($row) ? User::fromDatabaseRow($row) : null;
    }

    public function findById(int|string $id): ?User
    {
        $statement = $this->connection()->prepare(
            'SELECT id, name, email, rol, telefono, ultimo_acceso, created_at
             FROM users
             WHERE id = ?'
        );
        $statement->execute([$id]);
        $row = $statement->fetch();

        return is_array($row) ? User::fromDatabaseRow($row) : null;
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->connection()->prepare('SELECT id FROM users WHERE email = ?');
        $statement->execute([$email]);

        return (bool) $statement->fetch();
    }

    public function create(
        string $name,
        string $email,
        string $passwordHash,
        string $role,
        ?string $phone
    ): int {
        $connection = $this->connection();
        $statement = $connection->prepare(
            'INSERT INTO users (name, email, password, rol, telefono) VALUES (?, ?, ?, ?, ?)'
        );
        $statement->execute([$name, $email, $passwordHash, $role, $phone]);

        return (int) $connection->lastInsertId();
    }

    public function updateLastLogin(int|string $id): void
    {
        $statement = $this->connection()->prepare(
            'UPDATE users SET ultimo_acceso = NOW() WHERE id = ?'
        );
        $statement->execute([$id]);
    }
}
