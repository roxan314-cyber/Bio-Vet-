<?php
declare(strict_types=1);

namespace BioVet\App\Http\Controllers;

use BioVet\App\Http\HttpRequest;
use BioVet\App\Http\HttpResponse;
use BioVet\App\Services\HealthService;
use PDOException;

final class HealthController
{
    public function __construct(private readonly HealthService $health)
    {
    }

    public function check(HttpRequest $request): HttpResponse
    {
        unset($request);

        try {
            $this->health->check();
            return HttpResponse::json(200, ['status' => 'ok', 'database' => 'connected']);
        } catch (PDOException $exception) {
            error_log('BioVet health check database error: ' . $exception->getMessage());
            return HttpResponse::json(503, ['status' => 'error', 'database' => 'unavailable']);
        }
    }
}
