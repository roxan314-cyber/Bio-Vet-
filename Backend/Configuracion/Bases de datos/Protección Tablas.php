<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2)
    . DIRECTORY_SEPARATOR . 'Conexiones'
    . DIRECTORY_SEPARATOR . 'DBMS'
    . DIRECTORY_SEPARATOR . 'Conexión Bases de Datos.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config database.php';

class DatabaseProtectionSystem
{
    private MySQLConnectionManager $connectionManager;
    private array $allowedTables = [];
    private array $config = [
        'enable_query_logging' => true,
        'max_query_length' => 10000,
        'sensitive_tables' => ['users', 'passwords', 'tokens', 'sessions', 'credentials']
    ];
    private ?string $encryptionKey;

    public function __construct(MySQLConnectionManager $connectionManager, ?string $encryptionKey = null)
    {
        $this->connectionManager = $connectionManager;
        $configuredKey = $encryptionKey;
        if ($configuredKey === null || $configuredKey === '') {
            $configuredKey = getAppSetting('DATA_ENCRYPTION_KEY', 'DATA_ENCRYPTION_KEY');
        }
        $this->encryptionKey = $this->normalizeEncryptionKey($configuredKey);
    }

    public function secureQuery(
        string $query,
        array $params = [],
        ?string $connectionName = null,
        bool $allowWrite = false
    ): mysqli_result|mysqli_stmt|bool {
        $query = trim($query);
        if ($query === '' || strlen($query) > $this->config['max_query_length']) {
            throw new InvalidArgumentException('La consulta está vacía o excede la longitud permitida.');
        }

        if (!$allowWrite && !preg_match('/^(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)\b/i', $query)) {
            throw new RuntimeException('Solo se permiten consultas de lectura en este contexto.');
        }

        if ($this->config['enable_query_logging']) {
            error_log(sprintf(
                'BioVet database query: connection=%s, parameter_count=%d',
                $connectionName ?? 'default',
                count($params)
            ));
        }

        try {
            if ($params !== []) {
                return $this->connectionManager->executePrepared($query, $params, $connectionName);
            }

            return $this->connectionManager->query($query, $connectionName);
        } catch (Throwable $exception) {
            error_log(sprintf(
                'BioVet database query failed: connection=%s, exception=%s',
                $connectionName ?? 'default',
                get_class($exception)
            ));
            throw $exception;
        }
    }

    public function secureFetch(string $query, array $params = [], ?string $connectionName = null): array
    {
        $result = $this->secureQuery($query, $params, $connectionName);

        if ($result instanceof mysqli_stmt) {
            try {
                $rows = $result->get_result();
                if (!$rows instanceof mysqli_result) {
                    throw new RuntimeException('La consulta no devolvió un conjunto de resultados.');
                }
                return $rows->fetch_all(MYSQLI_ASSOC);
            } finally {
                $result->close();
            }
        }

        if (!$result instanceof mysqli_result) {
            throw new RuntimeException('La consulta no devolvió un conjunto de resultados.');
        }

        try {
            return $result->fetch_all(MYSQLI_ASSOC);
        } finally {
            $result->free();
        }
    }

    public function secureInsert(
        string $table,
        array $data,
        ?string $connectionName = null,
        array $encryptFields = []
    ): int {
        $this->checkTablePermissions($table, 'INSERT', $connectionName);
        $this->validateColumns($data);

        foreach ($encryptFields as $field) {
            if (!is_string($field) || !array_key_exists($field, $data)) {
                throw new InvalidArgumentException('El campo a cifrar no existe en los datos.');
            }
            $data[$field] = $this->encryptData($data[$field]);
        }

        return $this->connectionManager->insert($table, $data, $connectionName);
    }

    public function secureUpdate(
        string $table,
        array $data,
        string $where,
        array $whereParams = [],
        ?string $connectionName = null
    ): int {
        $this->checkTablePermissions($table, 'UPDATE', $connectionName);
        $this->validateColumns($data);

        return $this->connectionManager->update(
            $table,
            $data,
            $where,
            $whereParams,
            $connectionName
        );
    }

    public function encryptData(string $data): string
    {
        if ($this->encryptionKey === null) {
            throw new RuntimeException('Configura DATA_ENCRYPTION_KEY con una clave segura de 32 bytes.');
        }
        if (!function_exists('openssl_encrypt')) {
            throw new RuntimeException('La extensión OpenSSL de PHP es necesaria para cifrar datos.');
        }

        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $data,
            'aes-256-gcm',
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        if ($ciphertext === false || strlen($tag) !== 16) {
            throw new RuntimeException('No se pudieron cifrar los datos.');
        }

        return base64_encode('BVP1' . $iv . $tag . $ciphertext);
    }

    public function decryptData(string $encryptedData): string
    {
        if ($this->encryptionKey === null) {
            throw new RuntimeException('Configura DATA_ENCRYPTION_KEY con la misma clave usada para cifrar.');
        }
        if (!function_exists('openssl_decrypt')) {
            throw new RuntimeException('La extensión OpenSSL de PHP es necesaria para descifrar datos.');
        }

        $payload = base64_decode($encryptedData, true);
        if ($payload === false || strlen($payload) < 32 || substr($payload, 0, 4) !== 'BVP1') {
            throw new InvalidArgumentException('El valor cifrado no tiene un formato válido.');
        }

        $iv = substr($payload, 4, 12);
        $tag = substr($payload, 16, 16);
        $ciphertext = substr($payload, 32);
        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        if ($plaintext === false) {
            throw new RuntimeException('No se pudieron verificar o descifrar los datos.');
        }

        return $plaintext;
    }

    public function setConfig(string $key, mixed $value): bool
    {
        if (!array_key_exists($key, $this->config)) {
            return false;
        }

        if ($key === 'enable_query_logging' && !is_bool($value)) {
            throw new InvalidArgumentException('enable_query_logging debe ser booleano.');
        }
        if ($key === 'max_query_length' && (!is_int($value) || $value < 1)) {
            throw new InvalidArgumentException('max_query_length debe ser un entero positivo.');
        }
        if ($key === 'sensitive_tables') {
            if (!is_array($value)) {
                throw new InvalidArgumentException('sensitive_tables debe ser una lista de tablas.');
            }
            foreach ($value as $table) {
                if (!is_string($table) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
                    throw new InvalidArgumentException('El nombre de una tabla sensible no es válido.');
                }
            }
            $value = array_map('strtolower', $value);
        }

        $this->config[$key] = $value;
        return true;
    }

    public function allowTables(array $tables, string $database = '*'): void
    {
        if ($database !== '*' && !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $database)) {
            throw new InvalidArgumentException('El nombre de la base de datos no es válido.');
        }

        foreach ($tables as $table) {
            if (!is_string($table) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
                throw new InvalidArgumentException('El nombre de una tabla permitida no es válido.');
            }
            $this->allowedTables[] = $database === '*' ? $table : $database . '.' . $table;
        }
        $this->allowedTables = array_values(array_unique($this->allowedTables));
    }

    private function checkTablePermissions(string $table, string $operation, ?string $connectionName): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
            throw new InvalidArgumentException('El nombre de la tabla no es válido.');
        }

        if (in_array(strtolower($table), $this->config['sensitive_tables'], true)) {
            throw new RuntimeException("La operación $operation sobre la tabla sensible '$table' está bloqueada.");
        }

        if ($this->allowedTables === []) {
            return;
        }

        $database = $this->getCurrentDatabase($connectionName);
        if (!in_array($table, $this->allowedTables, true)
            && !in_array($database . '.' . $table, $this->allowedTables, true)) {
            throw new RuntimeException("La tabla '$database.$table' no está autorizada.");
        }
    }

    private function getCurrentDatabase(?string $connectionName): string
    {
        $result = $this->connectionManager->query('SELECT DATABASE()', $connectionName);
        if (!$result instanceof mysqli_result) {
            throw new RuntimeException('No se pudo determinar la base de datos activa.');
        }

        try {
            $row = $result->fetch_row();
        } finally {
            $result->free();
        }

        if (!is_array($row) || !is_string($row[0] ?? null) || $row[0] === '') {
            throw new RuntimeException('La conexión no tiene una base de datos seleccionada.');
        }

        return $row[0];
    }

    private function validateColumns(array $data): void
    {
        if ($data === []) {
            throw new InvalidArgumentException('Los datos no pueden estar vacíos.');
        }

        foreach (array_keys($data) as $column) {
            if (!is_string($column) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column)) {
                throw new InvalidArgumentException('El nombre de una columna no es válido.');
            }
        }
    }

    private function normalizeEncryptionKey(?string $key): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }

        if (strlen($key) === 32) {
            return $key;
        }

        $decoded = base64_decode($key, true);
        if ($decoded !== false && strlen($decoded) === 32) {
            return $decoded;
        }

        throw new InvalidArgumentException(
            'La clave de cifrado debe contener exactamente 32 bytes o su representación Base64.'
        );
    }
}
