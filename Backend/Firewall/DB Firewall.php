<?php
/**
 * Firewall para Base de Datos MySQL
 * Protege contra inyección SQL, fuerza bruta, y ataques comunes
 */

class DatabaseFirewall {
    private $blocked_ips = [];
    private $failed_attempts = [];
    private $log_file = 'security.log';
    private $max_attempts = 5;
    private $block_time = 900; // 15 minutos en segundos
    
    public function __construct() {
        $this->loadBlockedIPs();
        $this->loadFailedAttempts();
        $this->checkIfBlocked();
    }
    
    /**
     * Cargar IPs bloqueadas desde archivo
     */
    private function loadBlockedIPs() {
        if (file_exists('data/blocked_ips.json')) {
            $this->blocked_ips = json_decode(file_get_contents('data/blocked_ips.json'), true);
        }
    }
    
    /**
     * Cargar intentos fallidos
     */
    private function loadFailedAttempts() {
        if (file_exists('data/failed_attempts.json')) {
            $this->failed_attempts = json_decode(file_get_contents('data/failed_attempts.json'), true);
        }
    }
    
    /**
     * Guardar IPs bloqueadas
     */
    private function saveBlockedIPs() {
        if (!is_dir('data')) mkdir('data', 0755, true);
        file_put_contents('data/blocked_ips.json', json_encode($this->blocked_ips));
    }
    
    /**
     * Guardar intentos fallidos
     */
    private function saveFailedAttempts() {
        if (!is_dir('data')) mkdir('data', 0755, true);
        file_put_contents('data/failed_attempts.json', json_encode($this->failed_attempts));
    }
    
    /**
     * Verificar si la IP actual está bloqueada
     */
    private function checkIfBlocked() {
        $ip = $this->getClientIP();
        
        if (isset($this->blocked_ips[$ip]) && $this->blocked_ips[$ip] > time()) {
            $this->denyAccess("IP bloqueada por seguridad");
        }
        
        // Limpiar bloqueos expirados
        foreach ($this->blocked_ips as $ip => $expire_time) {
            if ($expire_time < time()) {
                unset($this->blocked_ips[$ip]);
            }
        }
    }
    
    /**
     * Obtener IP real del cliente
     */
    private function getClientIP() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ipaddress = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } else if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        
        // Validar IP
        if (!filter_var($ipaddress, FILTER_VALIDATE_IP)) {
            $ipaddress = '0.0.0.0';
        }
        
        return $ipaddress;
    }
    
    /**
     * Registrar intento fallido
     */
    public function registerFailedAttempt($reason = '') {
        $ip = $this->getClientIP();
        $time = time();
        
        if (!isset($this->failed_attempts[$ip])) {
            $this->failed_attempts[$ip] = [];
        }
        
        // Limpiar intentos antiguos (más de 1 hora)
        $this->failed_attempts[$ip] = array_filter($this->failed_attempts[$ip], function($timestamp) {
            return $timestamp > (time() - 3600);
        });
        
        // Agregar nuevo intento
        $this->failed_attempts[$ip][] = $time;
        $this->saveFailedAttempts();
        
        // Loggear intento
        $this->logSecurityEvent("Intento fallido desde $ip - Razón: $reason");
        
        // Verificar si debe bloquear
        if (count($this->failed_attempts[$ip]) >= $this->max_attempts) {
            $this->blockIP($ip, "Demasiados intentos fallidos");
        }
    }
    
    /**
     * Bloquear una IP
     */
    public function blockIP($ip, $reason = '') {
        $this->blocked_ips[$ip] = time() + $this->block_time;
        $this->saveBlockedIPs();
        $this->logSecurityEvent("IP $ip bloqueada - Razón: $reason");
    }
    
    /**
     * Denegar acceso y mostrar error genérico
     */
    private function denyAccess($message = 'Acceso denegado') {
        http_response_code(403);
        die(json_encode([
            'success' => false,
            'message' => 'Acceso denegado temporalmente',
            'code' => 'ACCESS_DENIED'
        ]));
    }
    
    /**
     * Validar entrada contra inyección SQL
     */
    public function validateInput($input, $field_name = 'unknown') {
        // Patrones maliciosos
        $patterns = [
            '/union.*select/i',
            '/select.*from/i',
            '/insert.*into/i',
            '/delete.*from/i',
            '/drop.*table/i',
            '/alter.*table/i',
            '/create.*table/i',
            '/update.*set/i',
            '/exec(\s|\+)+(s|x)p\w+/i',
            '/\/\*/',  // Comentarios SQL
            '/--/',
            '/;.*--/',
            '/or\s+1\s*=\s*1/i',
            '/or\s+1=1/i',
            '/\'\s+or\s+\'\'=\'/i',
            '/\'\s+or\s+1/i',
            '/\'\s+or\s+true/i'
        ];
        
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $this->validateInput($value, $key);
            }
        } else {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $input)) {
                    $this->registerFailedAttempt("Inyección SQL detectada en campo: $field_name");
                    $this->denyAccess("Entrada no válida");
                }
            }
        }
        
        return true;
    }
    
    /**
     * Sanitizar entrada para SQL
     */
    public function sanitizeForSQL($input, $conn) {
        if (is_array($input)) {
            return array_map(function($item) use ($conn) {
                return $this->sanitizeForSQL($item, $conn);
            }, $input);
        }
        
        return mysqli_real_escape_string($conn, strip_tags(trim($input)));
    }
    
    /**
     * Loggear eventos de seguridad
     */
    private function logSecurityEvent($message) {
        $log_entry = date('Y-m-d H:i:s') . " - " . $message . " - IP: " . $this->getClientIP() . "\n";
        
        if (!is_dir('logs')) mkdir('logs', 0755, true);
        file_put_contents('logs/' . $this->log_file, $log_entry, FILE_APPEND);
        
        // Rotar log si es muy grande (>10MB)
        if (filesize('logs/' . $this->log_file) > 10485760) {
            rename('logs/' . $this->log_file, 'logs/' . $this->log_file . '.' . date('Y-m-d-His'));
        }
    }
    
    /**
     * Verificar rate limit por IP
     */
    public function checkRateLimit($action, $limit = 30, $period = 60) {
        $ip = $this->getClientIP();
        $key = "rate_limit_{$action}_{$ip}";
        $requests = [];
        
        if (file_exists("data/{$key}.json")) {
            $requests = json_decode(file_get_contents("data/{$key}.json"), true);
        }
        
        // Limpiar requests antiguos
        $requests = array_filter($requests, function($timestamp) use ($period) {
            return $timestamp > (time() - $period);
        });
        
        if (count($requests) >= $limit) {
            $this->registerFailedAttempt("Rate limit excedido para acción: $action");
            $this->denyAccess("Demasiadas solicitudes");
        }
        
        $requests[] = time();
        file_put_contents("data/{$key}.json", json_encode($requests));
        
        return true;
    }
    
    /**
     * Validar formato de ID de paciente (ej: PET001)
     */
    public function validatePatientID($id) {
        if (!preg_match('/^PET[0-9]{3,6}$/', $id)) {
            $this->registerFailedAttempt("Formato de ID de paciente inválido: $id");
            return false;
        }
        return true;
    }
    
    /**
     * Validar formato de ID de cliente (ej: CLI001)
     */
    public function validateClientID($id) {
        if (!preg_match('/^CLI[0-9]{3,6}$/', $id)) {
            $this->registerFailedAttempt("Formato de ID de cliente inválido: $id");
            return false;
        }
        return true;
    }
    
    /**
     * Validar peso de mascota
     */
    public function validateWeight($weight) {
        if (!is_numeric($weight) || $weight < 0 || $weight > 500) {
            $this->registerFailedAttempt("Peso inválido: $weight");
            return false;
        }
        return true;
    }
    
    /**
     * Validar edad (solo caracteres permitidos)
     */
    public function validateAge($age) {
        if (!preg_match('/^[0-9\sA-Za-záéíóúñÑ]{1,30}$/', $age)) {
            $this->registerFailedAttempt("Formato de edad inválido: $age");
            return false;
        }
        return true;
    }
}

// Inicializar firewall
$firewall = new DatabaseFirewall();

// Verificar rate limit global
$firewall->checkRateLimit('global_request', 100, 60); // 100 requests por minuto

?>