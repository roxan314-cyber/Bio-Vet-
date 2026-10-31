<?php
function getAppSetting(string $environmentKey, string $dotenvKey, ?string $default = null): ?string
{
    $environmentValue = getenv($environmentKey);
    if ($environmentValue !== false && $environmentValue !== '') {
        return $environmentValue;
    }
    if ($environmentKey === 'DB_PASS') {
        $passwordAlias = getenv('DB_PASSWORD');
        if ($passwordAlias !== false && $passwordAlias !== '') {
            return $passwordAlias;
        }
    }

    static $dotenv = null;
    if ($dotenv === null) {
        $dotenv = [];
        $dotenvPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';

        if (is_file($dotenvPath)) {
            foreach (file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$key, $value] = explode('=', $line, 2);
                $dotenv[trim($key)] = trim(trim($value), "\"'");
            }
        }
    }

    if (isset($dotenv[$environmentKey])) {
        return $dotenv[$environmentKey];
    }
    if ($environmentKey === 'DB_PASS' && isset($dotenv['DB_PASSWORD'])) {
        return $dotenv['DB_PASSWORD'];
    }
    return $dotenv[$dotenvKey] ?? $default;
}

function getDBConnection() {
    $host = trim((string) getAppSetting('DB_HOST', 'database.default.hostname', '127.0.0.1'));
    $database = trim((string) getAppSetting('DB_NAME', 'database.default.database', 'biovet_db'));
    $username = trim((string) getAppSetting('DB_USER', 'database.default.username', 'root'));
    $password = getAppSetting('DB_PASS', 'database.default.password', '');
    $port = trim((string) getAppSetting('DB_PORT', 'DB_PORT', '3306'));

    if ($host === '' || strpbrk($host, ";\r\n") !== false) {
        throw new PDOException('DB_HOST is invalid.');
    }
    if ($database === '' || strpbrk($database, ";\r\n") !== false) {
        throw new PDOException('DB_NAME is invalid.');
    }
    if ($username === '') {
        throw new PDOException('DB_USER must not be empty.');
    }
    if (!ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
        throw new PDOException('DB_PORT must be a number between 1 and 65535.');
    }

    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    return $pdo;
}