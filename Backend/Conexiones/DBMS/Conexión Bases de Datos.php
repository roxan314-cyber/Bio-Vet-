<?php
declare(strict_types=1);

/**
 * Clase para gestión de múltiples conexiones MySQL
 * @author Backend
 * @version 1.0
 */
class MySQLConnectionManager {
    
    private $connections = [];
    private $activeConnection = null;
    private $configurations = [];
    private $activeTransactions = [];
    
    /**
     * Constructor - Carga configuraciones desde archivo o array
     * @param array $configs Configuraciones opcionales
     */
    public function __construct(array $configs = []) {
        if (!empty($configs)) {
            foreach ($configs as $name => $config) {
                $this->addConnectionConfig($name, $config);
            }
        }
        
        $configurationFile = __DIR__ . DIRECTORY_SEPARATOR . 'db_configs.json';
        if (is_file($configurationFile)) {
            $this->loadConfigurationsFromFile($configurationFile);
        }
    }
    
    /**
     * Agrega configuración para una base de datos
     * @param string $name Identificador de la conexión
     * @param array $config Configuración (host, user, pass, db, port, charset)
     * @return bool
     */
    public function addConnectionConfig(string $name, array $config): bool {
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
            throw new InvalidArgumentException('El nombre de conexión no es válido.');
        }

        $required = ['host', 'user', 'pass', 'db'];
        foreach ($required as $field) {
            if (!isset($config[$field])) {
                throw new Exception("Configuración incompleta para '$name': falta '$field'");
            }
        }

        foreach (['host', 'user', 'pass', 'db'] as $field) {
            if (!is_string($config[$field])) {
                throw new InvalidArgumentException("El campo '$field' debe ser texto.");
            }
        }

        $defaults = [
            'port' => 3306,
            'charset' => 'utf8mb4'
        ];

        $normalized = array_merge($defaults, $config);
        if (!is_int($normalized['port']) && !(is_string($normalized['port']) && ctype_digit($normalized['port']))) {
            throw new InvalidArgumentException('El puerto de la base de datos no es válido.');
        }
        $normalized['port'] = (int) $normalized['port'];
        if ($normalized['port'] < 1 || $normalized['port'] > 65535) {
            throw new InvalidArgumentException('El puerto de la base de datos debe estar entre 1 y 65535.');
        }
        if (!is_string($normalized['charset']) || !preg_match('/^[A-Za-z0-9_]+$/', $normalized['charset'])) {
            throw new InvalidArgumentException('El charset de la base de datos no es válido.');
        }

        $this->configurations[$name] = $normalized;
        return true;
    }
    
    /**
     * Conecta a una base de datos específica
     * @param string $name Identificador de la conexión
     * @param bool $forceNew Forzar nueva conexión aunque ya exista
     * @return mysqli
     */
    public function connect(string $name, bool $forceNew = false): mysqli {
        if ($forceNew && isset($this->activeTransactions[$name])) {
            throw new RuntimeException("No se puede reemplazar la conexión '$name' durante una transacción.");
        }

        if (!$forceNew && isset($this->connections[$name]) && $this->connections[$name] instanceof mysqli) {
            try {
                if ($this->connections[$name]->ping()) {
                    $this->activeConnection = $name;
                    return $this->connections[$name];
                }
            } catch (mysqli_sql_exception|\Error) {
                $this->discardConnection($name);
                throw new RuntimeException(
                    "Se perdió la conexión '$name'; la transacción activa no puede continuar y no se reconectó automáticamente."
                );
            }
        }
        
        if (!isset($this->configurations[$name])) {
            throw new Exception("Configuración no encontrada para: $name");
        }
        
        $config = $this->configurations[$name];
        
        if ($forceNew && isset($this->connections[$name])) {
            $this->connections[$name]->close();
            unset($this->connections[$name]);
        }

        $conn = null;
        try {
            $conn = new mysqli(
                $config['host'],
                $config['user'],
                $config['pass'],
                $config['db'],
                $config['port']
            );
            $conn->set_charset($config['charset']);
        } catch (mysqli_sql_exception $exception) {
            if ($conn instanceof mysqli) {
                $conn->close();
            }

            throw new RuntimeException(
                "No se pudo establecer la conexión '$name'. Revisa la configuración y la disponibilidad de MySQL.",
                0,
                $exception
            );
        }
        
        $this->connections[$name] = $conn;
        $this->activeConnection = $name;
        
        return $conn;
    }
    /**
     * Obtiene una conexión activa o la crea si no existe
     * @param string|null $name Nombre de la conexión
     * @return mysqli
     */
    public function getConnection(?string $name = null): mysqli {
        $connName = $name ?? $this->activeConnection;
        if (!$connName || !isset($this->connections[$connName])) {
            throw new Exception("No hay conexión activa o especificada");
        }
        
        // Verificar si la conexión sigue viva
        try {
            $isAlive = $this->connections[$connName]->ping();
        } catch (mysqli_sql_exception|\Error) {
            $isAlive = false;
        }

        if (!$isAlive) {
            if (isset($this->activeTransactions[$connName])) {
                $this->discardConnection($connName);
                throw new RuntimeException(
                    "Se perdió la conexión '$connName' durante una transacción. La transacción no se reconectó automáticamente."
                );
            }

            $this->connect($connName, true);
        }
        
        return $this->connections[$connName];
    }
    
    /**
     * Ejecuta una consulta SQL
     * @param string $sql Consulta SQL
     * @param string|null $connection Conexión específica
     * @return mysqli_result|bool
     */
    public function query(string $sql, ?string $connection = null): mysqli_result|bool {
        $conn = $this->getConnection($connection);
        $result = $conn->query($sql);
        
        if ($result === false) {
            throw new RuntimeException("Error al ejecutar la consulta: " . $conn->error);
        }
        
        return $result;
    }
    
    /**
     * Prepara y ejecuta una consulta con parámetros (prevención SQL injection)
     * @param string $sql Consulta con placeholders (?)
     * @param array $params Parámetros
     * @param string|null $connection Conexión específica
     * @return mysqli_stmt
     */
    public function executePrepared(string $sql, array $params = [], ?string $connection = null): mysqli_stmt {
        $conn = $this->getConnection($connection);
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new RuntimeException("Error preparando la consulta: " . $conn->error);
        }
        
        if (!empty($params)) {
            $types = '';
            $bindParams = [];
            
            foreach ($params as $param) {
                if (is_int($param) || is_bool($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } elseif (is_string($param) || $param === null) {
                    $types .= 's';
                } else {
                    $stmt->close();
                    throw new InvalidArgumentException('Los parámetros SQL deben ser valores escalares o null.');
                }
                $bindParams[] = $param;
            }

            $arguments = [$types];
            foreach ($bindParams as $index => &$value) {
                $arguments[] = &$value;
            }
            unset($value);

            try {
                $bound = call_user_func_array([$stmt, 'bind_param'], $arguments);
            } catch (mysqli_sql_exception $exception) {
                $stmt->close();
                throw new RuntimeException('No se pudieron asociar los parámetros de la consulta.', 0, $exception);
            }

            if (!$bound) {
                $stmt->close();
                throw new RuntimeException('No se pudieron asociar los parámetros de la consulta.');
            }
        }
        
        try {
            $stmt->execute();
        } catch (mysqli_sql_exception $exception) {
            $message = $stmt->error;
            $stmt->close();
            throw new RuntimeException("Error al ejecutar la consulta: " . $message, 0, $exception);
        }
        
        return $stmt;
    }
    
    /**
     * Obtiene un solo registro
     * @param string $sql Consulta SQL
     * @param array $params Parámetros
     * @param string|null $connection Conexión específica
     * @return array|null
     */
    public function fetchOne(string $sql, array $params = [], ?string $connection = null): ?array {
        if (empty($params)) {
            $result = $this->query($sql, $connection);
            if (!$result instanceof mysqli_result) {
                return null;
            }

            $row = $result->fetch_assoc();
            $result->free();
            return $row ?: null;
        } else {
            $stmt = $this->executePrepared($sql, $params, $connection);
            try {
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $result->free();
                return $row ?: null;
            } finally {
                $stmt->close();
            }
        }
    }
    
    /**
     * Obtiene todos los registros
     * @param string $sql Consulta SQL
     * @param array $params Parámetros
     * @param string|null $connection Conexión específica
     * @return array
     */
    public function fetchAll(string $sql, array $params = [], ?string $connection = null): array {
        if (empty($params)) {
            $result = $this->query($sql, $connection);
            if (!$result instanceof mysqli_result) {
                return [];
            }

            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
            return $rows;
        } else {
            $stmt = $this->executePrepared($sql, $params, $connection);
            try {
                $result = $stmt->get_result();
                $rows = $result->fetch_all(MYSQLI_ASSOC);
                $result->free();
                return $rows;
            } finally {
                $stmt->close();
            }
        }
    }
    
    /**
     * Inserta un registro y retorna el ID
     * @param string $table Tabla
     * @param array $data Datos (campo => valor)
     * @param string|null $connection Conexión específica
     * @return int
     */
    public function insert(string $table, array $data, ?string $connection = null): int {
        $this->assertIdentifier($table);
        if ($data === []) {
            throw new InvalidArgumentException('Se requiere al menos un campo para insertar.');
        }

        $fields = array_keys($data);
        foreach ($fields as $field) {
            if (!is_string($field)) {
                throw new InvalidArgumentException('Los nombres de columnas deben ser texto.');
            }
            $this->assertIdentifier($field);
        }

        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO `$table` (`" . implode('`, `', $fields) . "`) 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->executePrepared($sql, array_values($data), $connection);
        $stmt->close();
        
        $conn = $this->getConnection($connection);
        return $conn->insert_id;
    }
    
    /**
     * Actualiza registros
     * @param string $table Tabla
     * @param array $data Datos a actualizar
     * @param string $where Condición limitada a comparaciones de columna y placeholders
     * @param array $whereParams Parámetros para WHERE
     * @param string|null $connection Conexión específica
     * @return int
     */
    public function update(
        string $table,
        array $data,
        string $where,
        array $whereParams = [],
        ?string $connection = null
    ): int {
        $this->assertIdentifier($table);
        if ($data === []) {
            throw new InvalidArgumentException('Se requiere al menos un campo para actualizar.');
        }
        $this->validateWhereClause($where, count($whereParams));

        $sets = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if (!is_string($field)) {
                throw new InvalidArgumentException('Los nombres de columnas deben ser texto.');
            }
            $this->assertIdentifier($field);
            $sets[] = "`$field` = ?";
            $params[] = $value;
        }
        
        $params = array_merge($params, $whereParams);
        
        $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE $where";
        $stmt = $this->executePrepared($sql, $params, $connection);
        try {
            return $stmt->affected_rows;
        } finally {
            $stmt->close();
        }
    }
    
    /**
     * Inicia una transacción en la conexión especificada
     * @param string|null $connection Conexión específica
     */
    public function beginTransaction(?string $connection = null): void {
        $connectionName = $connection ?? $this->activeConnection;
        if ($connectionName === null) {
            throw new RuntimeException('No hay conexión activa o especificada.');
        }
        if (isset($this->activeTransactions[$connectionName])) {
            throw new RuntimeException("Ya hay una transacción activa en la conexión '$connectionName'.");
        }

        $conn = $this->getConnection($connection);
        $conn->begin_transaction();
        $this->activeTransactions[$connectionName] = true;
    }
    
    /**
     * Confirma una transacción
     * @param string|null $connection Conexión específica
     */
    public function commit(?string $connection = null): void {
        $connectionName = $connection ?? $this->activeConnection;
        $conn = $this->getConnection($connectionName);
        $conn->commit();
        unset($this->activeTransactions[$connectionName]);
    }
    
    /**
     * Revierte una transacción
     * @param string|null $connection Conexión específica
     */
    public function rollback(?string $connection = null): void {
        $connectionName = $connection ?? $this->activeConnection;
        $conn = $this->getConnection($connectionName);
        $conn->rollback();
        unset($this->activeTransactions[$connectionName]);
    }
    
    /**
     * Cierra una conexión específica
     * @param string|null $name Nombre de la conexión
     */
    public function closeConnection(?string $name = null): void {
        if ($name && isset($this->connections[$name])) {
            $this->discardConnection($name);
            if ($this->activeConnection === $name) {
                $remainingConnections = array_keys($this->connections);
                $this->activeConnection = $remainingConnections[0] ?? null;
            }
        } elseif (!$name && $this->activeConnection) {
            $this->closeConnection($this->activeConnection);
        }
    }
    
    /**
     * Cierra todas las conexiones
     */
    public function closeAll(): void {
        foreach ($this->connections as $name => $conn) {
            if ($conn instanceof mysqli) {
                $this->discardConnection($name);
            }
        }
        $this->connections = [];
        $this->activeTransactions = [];
        $this->activeConnection = null;
    }
    
    /**
     * Carga configuraciones desde archivo JSON
     * @param string $file Ruta del archivo
     */
    public function loadConfigurationsFromFile(string $file): bool {
        if (!is_file($file) || !is_readable($file)) {
            return false;
        }
        
        $content = file_get_contents($file);
        if ($content === false) {
            throw new RuntimeException("No se pudo leer el archivo de configuración '$file'.");
        }

        try {
            $configs = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                "El archivo de configuración '$file' contiene JSON inválido.",
                0,
                $exception
            );
        }

        if (!is_array($configs)) {
            throw new RuntimeException("El archivo de configuración '$file' debe contener un objeto JSON.");
        }

        foreach ($configs as $name => $config) {
            if (!is_array($config)) {
                throw new RuntimeException("La configuración '$name' debe ser un objeto JSON.");
            }
            $this->addConnectionConfig((string) $name, $config);
        }

        return true;
    }
    
    /**
     * Destructor - Cierra todas las conexiones
     */
    public function __destruct() {
        $this->closeAll();
    }

    private function assertIdentifier(string $identifier): void {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new InvalidArgumentException('El nombre de tabla o columna no es válido.');
        }
    }

    private function validateWhereClause(string $where, int $parameterCount): void {
        preg_match_all(
            '/[A-Za-z_][A-Za-z0-9_]*|<>|!=|<=|>=|=|<|>|\?|\\(|\\)|,/',
            $where,
            $matches
        );
        $tokens = $matches[0];
        $remaining = preg_replace(
            '/[A-Za-z_][A-Za-z0-9_]*|<>|!=|<=|>=|=|<|>|\?|\\(|\\)|,|\s+/',
            '',
            $where
        );

        if ($where === '' || $remaining !== '' || $tokens === []) {
            throw new InvalidArgumentException('La condición WHERE contiene sintaxis no permitida.');
        }

        $index = 0;
        $placeholders = 0;
        $this->parseWhereExpression($tokens, $index, $placeholders);

        if ($index !== count($tokens) || $placeholders !== $parameterCount) {
            throw new InvalidArgumentException('La condición WHERE y sus parámetros no coinciden.');
        }
    }

    private function parseWhereExpression(array $tokens, int &$index, int &$placeholders): void {
        $this->parseWhereConjunction($tokens, $index, $placeholders);

        while (isset($tokens[$index]) && strcasecmp($tokens[$index], 'OR') === 0) {
            $index++;
            $this->parseWhereConjunction($tokens, $index, $placeholders);
        }
    }

    private function parseWhereConjunction(array $tokens, int &$index, int &$placeholders): void {
        $this->parseWhereCondition($tokens, $index, $placeholders);

        while (isset($tokens[$index]) && strcasecmp($tokens[$index], 'AND') === 0) {
            $index++;
            $this->parseWhereCondition($tokens, $index, $placeholders);
        }
    }

    private function parseWhereCondition(array $tokens, int &$index, int &$placeholders): void {
        if (($tokens[$index] ?? null) === '(') {
            $index++;
            $this->parseWhereExpression($tokens, $index, $placeholders);
            if (($tokens[$index] ?? null) !== ')') {
                throw new InvalidArgumentException('La condición WHERE tiene paréntesis incompletos.');
            }
            $index++;
            return;
        }

        $column = $tokens[$index] ?? null;
        if (!is_string($column) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column)) {
            throw new InvalidArgumentException('La condición WHERE debe comenzar con un nombre de columna válido.');
        }
        $index++;

        $operator = strtoupper($tokens[$index] ?? '');
        if (in_array($operator, ['=', '!=', '<>', '<', '<=', '>', '>='], true)) {
            $index++;
            if (($tokens[$index] ?? null) !== '?') {
                throw new InvalidArgumentException('Las comparaciones WHERE deben usar placeholders.');
            }
            $placeholders++;
            $index++;
            return;
        }

        if ($operator === 'IS') {
            $index++;
            if (strcasecmp($tokens[$index] ?? '', 'NOT') === 0) {
                $index++;
            }
            if (strcasecmp($tokens[$index] ?? '', 'NULL') !== 0) {
                throw new InvalidArgumentException('IS solo puede compararse con NULL en la condición WHERE.');
            }
            $index++;
            return;
        }

        if (strcasecmp($tokens[$index] ?? '', 'NOT') === 0) {
            $index++;
            $operator = strtoupper($tokens[$index] ?? '');
        }

        if ($operator === 'LIKE') {
            $index++;
            if (($tokens[$index] ?? null) !== '?') {
                throw new InvalidArgumentException('LIKE debe usar un placeholder en la condición WHERE.');
            }
            $placeholders++;
            $index++;
            return;
        }

        if ($operator === 'IN') {
            $index++;
            if (($tokens[$index] ?? null) !== '(') {
                throw new InvalidArgumentException('IN debe recibir una lista de placeholders.');
            }
            $index++;
            if (($tokens[$index] ?? null) !== '?') {
                throw new InvalidArgumentException('IN debe recibir al menos un placeholder.');
            }

            do {
                $placeholders++;
                $index++;
                if (($tokens[$index] ?? null) !== ',') {
                    break;
                }
                $index++;
                if (($tokens[$index] ?? null) !== '?') {
                    throw new InvalidArgumentException('La lista IN solo puede contener placeholders.');
                }
            } while (true);

            if (($tokens[$index] ?? null) !== ')') {
                throw new InvalidArgumentException('La lista IN tiene paréntesis incompletos.');
            }
            $index++;
            return;
        }

        throw new InvalidArgumentException('Operador no permitido en la condición WHERE.');
    }

    private function discardConnection(string $name): void {
        if (!isset($this->connections[$name])) {
            unset($this->activeTransactions[$name]);
            return;
        }

        $connection = $this->connections[$name];
        if (isset($this->activeTransactions[$name])) {
            try {
                if ($connection->ping()) {
                    $connection->rollback();
                }
            } catch (mysqli_sql_exception|\Error) {
                // Closing a lost connection lets MySQL roll back its open transaction.
            }
        }

        try {
            $connection->close();
        } catch (\Error) {
            // The connection may already have been closed by the driver or caller.
        }
        unset($this->connections[$name], $this->activeTransactions[$name]);
    }
}