<?php
declare(strict_types=1);

require_once __DIR__ . '/Configuracion/config database.php';
require_once __DIR__ . '/Apis/Helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'BioVet\\App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = __DIR__ . '/app/' . $relativePath . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

function createBioVetApplication(): \BioVet\App\Application
{
    return \BioVet\App\Application::fromEnvironment();
}

function loadBioVetRouteRegistry(): array
{
    $json = file_get_contents(__DIR__ . '/routes/web-routes.json');
    if (!is_string($json)) {
        throw new RuntimeException('Could not read the BioVet web route registry.');
    }

    $registry = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($registry)) {
        throw new RuntimeException('The BioVet web route registry must be a JSON object.');
    }

    return $registry;
}

function emitBioVetResponse(\BioVet\App\Http\HttpResponse $response): void
{
    http_response_code($response->status);
    foreach ($response->headers as $name => $value) {
        header($name . ': ' . $value);
    }
    echo $response->body;
}

function runBioVetLegacyEndpoint(string $path): void
{
    $registry = loadBioVetRouteRegistry();
    $route = $registry['api'][$path] ?? null;
    if (!is_array($route)) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'mensaje' => 'Ruta API no encontrada']);
        return;
    }

    $bodyLimit = ($route['action'] ?? null) === 'register' ? 65537 : null;
    $body = file_get_contents('php://input', false, null, 0, $bodyLimit);
    $request = \BioVet\App\Http\HttpRequest::fromGlobals($body);
    emitBioVetResponse(createBioVetApplication()->dispatch($route, $request));
}
