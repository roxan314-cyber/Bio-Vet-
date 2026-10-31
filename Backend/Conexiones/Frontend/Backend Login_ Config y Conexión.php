<?php
session_start();

// Configuración de base de datos (MySQL)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'login_system');

// Opcional: Usar archivo JSON como base de datos simple
define('USE_JSON', true); // Cambiar a false para usar MySQL
define('JSON_FILE', __DIR__ . '/usuarios.json');

// Función para conectar a MySQL
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

// Función para validar usuario (versión JSON)
function validateUserJSON($username, $password) {
    if (!file_exists(JSON_FILE)) {
        return false;
    }
    
    $users = json_decode(file_get_contents(JSON_FILE), true);
    
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            return $user;
        }
    }
    return false;
}

// Función para validar usuario (versión MySQL)
function validateUserMySQL($username, $password) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}
?>