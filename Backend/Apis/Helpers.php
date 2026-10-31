<?php
function base64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode(string $data): string|false
{
    $decoded = strtr($data, '-_', '+/');
    $decoded .= str_repeat('=', (4 - strlen($decoded) % 4) % 4);

    return base64_decode($decoded, true);
}

function generarJWT(array $payload, ?string $secret = null, int $expiresIn = 3600): string
{
    $secret ??= getAppSetting('JWT_SECRET', 'JWT_SECRET');
    if ($secret === null || strlen($secret) < 32) {
        throw new RuntimeException('JWT_SECRET must contain at least 32 characters.');
    }

    $payload['iat'] = time();
    $payload['exp'] = time() + $expiresIn;
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    $payloadJson = json_encode($payload);

    $base64Header = base64UrlEncode($header);
    $base64Payload = base64UrlEncode($payloadJson);

    $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);
    $base64Signature = base64UrlEncode($signature);

    return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
}

function verificarJWT(string $token, ?string $secret = null): ?array
{
    $secret ??= getAppSetting('JWT_SECRET', 'JWT_SECRET');
    if ($secret === null || strlen($secret) < 32) {
        throw new RuntimeException('JWT_SECRET must contain at least 32 characters.');
    }

    $parts = explode('.', $token);
    if (count($parts) !== 3 || in_array('', $parts, true)) {
        return null;
    }

    list($base64Header, $base64Payload, $base64Signature) = $parts;

    $header = base64UrlDecode($base64Header);
    $payloadJson = base64UrlDecode($base64Payload);
    $signature = base64UrlDecode($base64Signature);
    if ($header === false || $payloadJson === false || $signature === false) {
        return null;
    }

    $decodedHeader = json_decode($header, true);
    if (!is_array($decodedHeader) || ($decodedHeader['alg'] ?? null) !== 'HS256') {
        return null;
    }

    $expectedSignature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);
    if (!hash_equals($expectedSignature, $signature)) {
        return null;
    }

    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)
        || !isset($payload['user_id'], $payload['exp'])
        || !is_numeric($payload['exp'])
        || (int) $payload['exp'] <= time()) {
        return null;
    }

    return $payload;
}

function obtenerTokenBearer(): ?string
{
    $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

    if ($authorization === null && function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                $authorization = $value;
                break;
            }
        }
    }

    if (is_string($authorization) && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        return trim($matches[1]);
    }

    // Buscar en query string (para pruebas)
    if (isset($_GET['token']) && is_string($_GET['token'])) {
        return $_GET['token'];
    }

    return null;
}