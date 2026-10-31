<?php
require_once 'db_firewall.php';

class SecureDatabaseConnection {
    private $conn;
    private $firewall;
    private $is_transaction = false;
    
    public function __construct() {
        global $firewall;
        $this->firewall = $firewall;
        $this->connect();
    }
    
    /**
     * Establecer conexión segura
     */
    private function connect() {
        // Configuración desde variables de entorno
        $host = getenv('DB_HOST') ?: 'localhost';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $dbname = getenv('DB_NAME') ?: 'veterinaria_db';
        
        // Puerto no estándar para ofuscación
        $port = getenv('DB_PORT') ?: '3307';
        
        try {
            $this->conn = new mysqli($host, $user, $pass, $dbname, $port);
            
            if ($this->conn->connect_error) {
                throw new Exception("Error de conexión");
            }
            
            // Configurar charset a UTF-8
            $this->conn->set_charset("utf8mb4");
            
            // Modo de error silencioso (no revelar información)
            mysqli_report(MYSQLI_REPORT_OFF);
            
        } catch (Exception $e) {
            $this->firewall->logSecurityEvent("Error de conexión a DB");
            die(json_encode([
                'success' => false,
                'message' => 'Error interno del servidor'
            ]));
        }
    }
    
    /**
     * Ejecutar query con protección firewall
     */
    public function executeQuery($sql, $params = [], $types = "") {
        // Validar SQL contra inyección
        $this->firewall->validateInput($sql, 'sql_query');
        
        // Verificar rate limit por query
        $this->firewall->checkRateLimit('db_query', 50, 60);
        
        try {
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                $this->firewall->registerFailedAttempt("Error de preparación SQL");
                throw new Exception("Error en consulta");
            }
            
            if (!empty($params)) {
                if (empty($types)) {
                    $types = str_repeat('s', count($params));
                }
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            
            // Registrar éxito (limpiar intentos fallidos)
            $this->clearFailedAttempts();
            
            return $stmt;
            
        } catch (Exception $e) {
            $this->firewall->registerFailedAttempt("Error en ejecución SQL");
            $this->firewall->logSecurityEvent("SQL Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Limpiar intentos fallidos de esta IP
     */
    private function clearFailedAttempts() {
        // Implementación según necesidad
    }
    
    /**
     * Insertar paciente con validaciones firewall
     */
    public function insertPaciente($data) {
        // Validaciones firewall
        if (!$this->firewall->validatePatientID($data['id_paciente'])) {
            return ['success' => false, 'message' => 'ID de paciente inválido'];
        }
        
        if (!$this->firewall->validateClientID($data['id_cliente'])) {
            return ['success' => false, 'message' => 'ID de cliente inválido'];
        }
        
        if (!$this->firewall->validateWeight($data['peso_kg'])) {
            return ['success' => false, 'message' => 'Peso inválido'];
        }
        
        if (!$this->firewall->validateAge($data['edad'])) {
            return ['success' => false, 'message' => 'Edad inválida'];
        }
        
        // Sanitizar datos
        $sanitized_data = $this->firewall->sanitizeForSQL($data, $this->conn);
        
        $sql = "INSERT INTO Pacientes (id_paciente, nombre_paciente, raza, sexo, edad, peso_kg, id_cliente) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->executeQuery(
            $sql,
            [
                $sanitized_data['id_paciente'],
                $sanitized_data['nombre_paciente'],
                $sanitized_data['raza'],
                $sanitized_data['sexo'],
                $sanitized_data['edad'],
                $sanitized_data['peso_kg'],
                $sanitized_data['id_cliente']
            ],
            "sssssds"
        );
        
        if ($stmt) {
            return ['success' => true, 'message' => 'Paciente registrado exitosamente'];
        } else {
            return ['success' => false, 'message' => 'Error al registrar paciente'];
        }
    }
    
    /**
     * Obtener pacientes por cliente
     */
    public function getPacientesByCliente($id_cliente) {
        if (!$this->firewall->validateClientID($id_cliente)) {
            return [];
        }
        
        $sql = "SELECT * FROM Pacientes WHERE id_cliente = ?";
        $stmt = $this->executeQuery($sql, [$id_cliente], "s");
        
        if ($stmt) {
            $result = $stmt->get_result();
            $pacientes = [];
            while ($row = $result->fetch_assoc()) {
                $pacientes[] = $row;
            }
            return $pacientes;
        }
        
        return [];
    }
    
    /**
     * Actualizar paciente
     */
    public function updatePaciente($id_paciente, $data) {
        if (!$this->firewall->validatePatientID($id_paciente)) {
            return false;
        }
        
        $sql = "UPDATE Pacientes SET nombre_paciente = ?, raza = ?, sexo = ?, edad = ?, peso_kg = ? 
                WHERE id_paciente = ?";
        
        $stmt = $this->executeQuery(
            $sql,
            [
                $data['nombre_paciente'],
                $data['raza'],
                $data['sexo'],
                $data['edad'],
                $data['peso_kg'],
                $id_paciente
            ],
            "ssssds"
        );
        
        return $stmt !== false;
    }
    
    /**
     * Eliminar paciente (con verificación de permisos)
     */
    public function deletePaciente($id_paciente, $verification_code = null) {
        // Requerir código de verificación para eliminar
        if (!$verification_code || $verification_code !== $_SESSION['delete_token']) {
            $this->firewall->registerFailedAttempt("Intento de eliminación sin verificación");
            return false;
        }
        
        if (!$this->firewall->validatePatientID($id_paciente)) {
            return false;
        }
        
        $sql = "DELETE FROM Pacientes WHERE id_paciente = ?";
        $stmt = $this->executeQuery($sql, [$id_paciente], "s");
        
        return $stmt !== false;
    }
    
    /**
     * Búsqueda segura de pacientes
     */
    public function searchPacientes($keyword) {
        // Limitar keywords peligrosas
        $keyword = preg_replace('/[^\p{L}\p{N}\s]/u', '', $keyword);
        
        $sql = "SELECT * FROM Pacientes WHERE nombre_paciente LIKE ? OR raza LIKE ?";
        $search_term = "%{$keyword}%";
        
        $stmt = $this->executeQuery($sql, [$search_term, $search_term], "ss");
        
        if ($stmt) {
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        
        return [];
    }
    
    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
    
    public function __destruct() {
        $this->close();
    }
}
?>