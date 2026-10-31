<?php
declare(strict_types=1);

namespace BioVet\App\Repositories;

use Closure;
use PDO;

final class DatabaseHealthRepository
{
    private Closure $connectionFactory;

    public function __construct(callable $connectionFactory)
    {
        $this->connectionFactory = Closure::fromCallable($connectionFactory);
    }

    public function check(): void
    {
        $connection = ($this->connectionFactory)();
        if (!$connection instanceof PDO) {
            throw new \LogicException('The database connection factory must return PDO.');
        }

        $connection->query('SELECT 1');
    }
}
