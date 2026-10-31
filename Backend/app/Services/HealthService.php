<?php
declare(strict_types=1);

namespace BioVet\App\Services;

use BioVet\App\Repositories\DatabaseHealthRepository;

final class HealthService
{
    public function __construct(private readonly DatabaseHealthRepository $database)
    {
    }

    public function check(): void
    {
        $this->database->check();
    }
}
