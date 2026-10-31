<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Conexión Bases de Datos.php';

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
}

$projectRoot = dirname(__DIR__, 4);
$dotenvPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';
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
    $password = $dotenv['password'] ?? '';
}
$port = getenv('DB_PORT');
$port = $port === false || $port === '' ? 3306 : (int) $port;

if ($database === '' || $username === '') {
    throw new RuntimeException('Configura database.default.database y database.default.username en Proyecto/.env.');
}

$connectionName = 'test';
$table = 'manager_test_' . bin2hex(random_bytes(6));
$transactionTable = 'manager_transaction_test_' . bin2hex(random_bytes(6));
$transactionTableCreated = false;
$manager = new MySQLConnectionManager([
    $connectionName => [
        'host' => $host,
        'user' => $username,
        'pass' => $password,
        'db' => $database,
        'port' => $port
    ]
]);

try {
    $manager->connect($connectionName);
    $manager->query(
        "CREATE TEMPORARY TABLE `$table` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            label VARCHAR(80) NOT NULL,
            quantity INT NOT NULL,
            amount DECIMAL(8,2) NULL
        )",
        $connectionName
    );

    $statement = $manager->executePrepared(
        'SELECT ? AS integer_value, ? AS decimal_value, ? AS null_value',
        [7, 2.5, null],
        $connectionName
    );
    try {
        $values = $statement->get_result()->fetch_assoc();
    } finally {
        $statement->close();
    }

    assertSameValue(7, (int) $values['integer_value'], 'Integer parameter binding failed.');
    assertSameValue(2.5, (float) $values['decimal_value'], 'Float parameter binding failed.');
    assertSameValue(null, $values['null_value'], 'NULL parameter binding failed.');

    $id = $manager->insert($table, [
        'label' => 'initial',
        'quantity' => 2,
        'amount' => null
    ], $connectionName);

    $manager->update($table, ['label' => 'updated'], 'id = ?', [$id], $connectionName);
    $record = $manager->fetchOne(
        "SELECT id, label, quantity, amount FROM `$table` WHERE id = ?",
        [$id],
        $connectionName
    );
    assertSameValue('updated', $record['label'], 'Insert/update/fetch operations failed.');
    assertSameValue(null, $record['amount'], 'NULL value was not preserved.');

    assertSameValue(
        1,
        $manager->update($table, ['quantity' => 3], '(id = ? AND label LIKE ?) OR id IN (?, ?)', [
            $id,
            'updated',
            $id + 1,
            $id + 2
        ], $connectionName),
        'Supported grouped WHERE conditions failed.'
    );

    $manager->beginTransaction($connectionName);
    $insert = $manager->executePrepared(
        "INSERT INTO `$table` (label, quantity) VALUES (?, ?)",
        ['rollback', 9],
        $connectionName
    );
    $insert->close();
    $manager->rollback($connectionName);

    assertSameValue(
        [],
        $manager->fetchAll("SELECT id FROM `$table` WHERE label = ?", ['rollback'], $connectionName),
        'Transaction rollback failed.'
    );

    $invalidInputWasRejected = false;
    try {
        $manager->insert('invalid`table', ['label' => 'test'], $connectionName);
    } catch (InvalidArgumentException) {
        $invalidInputWasRejected = true;
    }
    assertSameValue(true, $invalidInputWasRejected, 'Invalid table identifier was accepted.');

    $invalidInputWasRejected = false;
    try {
        $manager->update($table, ['label' => 'test'], '', [], $connectionName);
    } catch (InvalidArgumentException) {
        $invalidInputWasRejected = true;
    }
    assertSameValue(true, $invalidInputWasRejected, 'An empty WHERE clause was accepted.');

    foreach ([
        ['id = ? OR 1 = 1', [$id]],
        ["id = ?; DELETE FROM users", [$id]],
        ["id = '1'", []],
        ['id = ? AND label = ?', [$id]]
    ] as [$where, $params]) {
        $invalidInputWasRejected = false;
        try {
            $manager->update($table, ['label' => 'test'], $where, $params, $connectionName);
        } catch (InvalidArgumentException) {
            $invalidInputWasRejected = true;
        }
        assertSameValue(true, $invalidInputWasRejected, 'An unsafe or mismatched WHERE condition was accepted.');
    }

        $manager->query(
            "CREATE TABLE `$transactionTable` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                label VARCHAR(80) NOT NULL
            ) ENGINE=InnoDB",
            $connectionName
        );
        $transactionTableCreated = true;
        $manager->beginTransaction($connectionName);
        $pendingInsert = $manager->executePrepared(
            "INSERT INTO `$transactionTable` (label) VALUES (?)",
            ['must-rollback'],
            $connectionName
        );
    $pendingInsert->close();
    $manager->getConnection($connectionName)->close();

    $connectionLossWasReported = false;
    try {
        $manager->fetchOne("SELECT id FROM `$table`", [], $connectionName);
    } catch (RuntimeException $exception) {
        $connectionLossWasReported = str_contains($exception->getMessage(), 'durante una transacción');
    }
    assertSameValue(true, $connectionLossWasReported, 'Connection loss during a transaction was not reported.');

    $manager->connect($connectionName);
    $persistedRows = $manager->fetchOne(
        "SELECT COUNT(*) AS total FROM `$transactionTable` WHERE label = ?",
        ['must-rollback'],
        $connectionName
    );
    assertSameValue(0, (int) $persistedRows['total'], 'The lost transaction was committed or reused.');

    $manager->query("DROP TABLE `$transactionTable`", $connectionName);
    $transactionTableCreated = false;

    echo "Connection, prepared parameters, CRUD, transactions, reconnection safety, and input validation passed.\n";
} finally {
    try {
        if ($transactionTableCreated) {
            $manager->connect($connectionName);
            $manager->query("DROP TABLE IF EXISTS `$transactionTable`", $connectionName);
        }
    } finally {
        $manager->closeAll();
    }
}
