<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3)
    . DIRECTORY_SEPARATOR . 'Conexiones'
    . DIRECTORY_SEPARATOR . 'DBMS'
    . DIRECTORY_SEPARATOR . 'Conexión Bases de Datos.php';
require_once dirname(__DIR__, 5)
    . DIRECTORY_SEPARATOR . 'Protección Tablas.php';

function assertProtectionSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
}

function expectProtectionException(callable $operation, string $message): void
{
    try {
        $operation();
    } catch (InvalidArgumentException|RuntimeException) {
        return;
    }
    throw new RuntimeException($message);
}

$projectRoot = dirname(__DIR__, 5);
$dotenvPath = $projectRoot . DIRECTORY_SEPARATOR . 'Proyecto' . DIRECTORY_SEPARATOR . '.env';
$dotenv = [];
if (is_file($dotenvPath)) {
    foreach (file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (preg_match('/^\s*database\.default\.([A-Za-z0-9_]+)\s*=\s*(.*?)\s*$/', $line, $matches)) {
            $dotenv[$matches[1]] = trim($matches[2], "\"'");
        }
    }
}

$host = getenv('DB_HOST') ?: ($dotenv['hostname'] ?? '127.0.0.1');
$database = getenv('DB_NAME') ?: ($dotenv['database'] ?? '');
$username = getenv('DB_USER') ?: ($dotenv['username'] ?? '');
$password = getenv('DB_PASS');
if ($password === false) {
    $password = getenv('DB_PASSWORD');
}
if ($password === false) {
    $password = $dotenv['password'] ?? '';
}
$portValue = getenv('DB_PORT');
$port = $portValue === false || $portValue === '' ? 3306 : (int) $portValue;

if ($database === '' || $username === '') {
    throw new RuntimeException('Configura database.default.database y database.default.username en Proyecto/.env.');
}

$table = 'protection_test_' . bin2hex(random_bytes(6));
$manager = new MySQLConnectionManager([
    'test' => [
        'host' => $host,
        'user' => $username,
        'pass' => $password,
        'db' => $database,
        'port' => $port
    ]
]);
$protection = new DatabaseProtectionSystem($manager, '0123456789abcdef0123456789abcdef');
$protection->setConfig('enable_query_logging', false);

try {
    $manager->connect('test');
    $manager->query(
        "CREATE TEMPORARY TABLE `$table` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            label VARCHAR(120) NOT NULL,
            quantity INT NOT NULL
        )",
        'test'
    );
    $protection->allowTables([$table], $database);

    $row = $protection->secureFetch('SELECT ? AS id, ? AS label', [7, '<script>kept as data</script>'], 'test');
    assertProtectionSame(7, (int) $row[0]['id'], 'Prepared secure fetch returned an incorrect value.');
    assertProtectionSame(
        '<script>kept as data</script>',
        $row[0]['label'],
        'Database data was altered instead of being escaped at output.'
    );

    $insertedId = $protection->secureInsert(
        $table,
        ['label' => 'before', 'quantity' => 1],
        'test'
    );
    assertProtectionSame(true, $insertedId > 0, 'Secure insert did not return an inserted ID.');
    assertProtectionSame(
        1,
        $protection->secureUpdate($table, ['quantity' => 2], 'id = ?', [$insertedId], 'test'),
        'Secure update did not report the updated row.'
    );

    $record = $protection->secureFetch(
        "SELECT label, quantity FROM `$table` WHERE id = ?",
        [$insertedId],
        'test'
    );
    assertProtectionSame(2, (int) $record[0]['quantity'], 'Secure update did not persist its change.');

    expectProtectionException(
        static fn() => $protection->secureQuery('DELETE FROM users WHERE id = 1', [], 'test'),
        'A write query was allowed in the read-only query context.'
    );
    expectProtectionException(
        static fn() => $protection->secureInsert('users', ['email' => 'not-written'], 'test'),
        'A write to the sensitive users table was allowed.'
    );
    expectProtectionException(
        static fn() => $protection->secureInsert('invalid`table', ['label' => 'test'], 'test'),
        'An invalid table identifier was accepted.'
    );

    $encrypted = $protection->encryptData('persistent secret');
    assertProtectionSame('persistent secret', $protection->decryptData($encrypted), 'Encryption round-trip failed.');
    $secondProtectionInstance = new DatabaseProtectionSystem(
        $manager,
        '0123456789abcdef0123456789abcdef'
    );
    assertProtectionSame(
        'persistent secret',
        $secondProtectionInstance->decryptData($encrypted),
        'Encryption did not remain compatible across instances using the same key.'
    );

    $tampered = base64_decode($encrypted, true);
    $tampered[strlen($tampered) - 1] = $tampered[strlen($tampered) - 1] ^ "\x01";
    expectProtectionException(
        static fn() => $protection->decryptData(base64_encode($tampered)),
        'Tampered encrypted data was accepted.'
    );

    echo "Secure queries, table permissions, persistent authenticated encryption, and input validation passed.\n";
} finally {
    $manager->closeAll();
}
