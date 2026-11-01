<?php
declare(strict_types=1);

require_once __DIR__ . '/Backend/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$registry = loadBioVetRouteRegistry();

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (isset($registry['api'][$path])) {
    $route = $registry['api'][$path];
    $bodyLimit = ($route['action'] ?? null) === 'register' ? 65537 : null;
    $body = file_get_contents('php://input', false, null, 0, $bodyLimit);
    $request = \BioVet\App\Http\HttpRequest::fromGlobals($body);
    emitBioVetResponse(createBioVetApplication()->dispatch($route, $request));
    return true;
}

if (isset($registry['redirects'][$path]) && ($registry['redirects'][$path]['php'] ?? false)) {
    $redirect = $registry['redirects'][$path];
    header('Location: ' . $redirect['to'], true, (int) $redirect['status']);
    return true;
}

if (isset($registry['pages'][$path]) && ($registry['pages'][$path]['php'] ?? false)) {
    $page = $registry['pages'][$path];
    header('Content-Type: ' . $page['content_type']);
    readfile(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $page['file']));
    return true;
}

if (isset($registry['assets'][$path])) {
    $asset = $registry['assets'][$path];
    header('Content-Type: ' . $asset['content_type']);
    header('Cache-Control: public, max-age=3600');
    readfile(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $asset['file']));
    return true;
}

if (str_starts_with($path, '/api/')) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(404);
    echo json_encode(['success' => false, 'mensaje' => 'Ruta API no encontrada']);
    return true;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Página no encontrada';
return true;
