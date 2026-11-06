<?php
declare(strict_types=1);

// 1. Iniciar sesión de forma segura 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de base de datos (MySQL)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'biovet_db'); // 

// Opcional: Usar archivo JSON como respaldo o desarrollo local
define('USE_JSON', false); 
define('JSON_FILE', __DIR__ . '/usuarios.json');

/**
 * Obtiene la conexión PDO de forma segura.
 * Lanza una excepción en lugar de usar die() para permitir que el endpoint devuelva un JSON 500 limpio.
 */
function getDBConnection(): PDO {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    return new PDO($dsn, DB_USER, DB_PASS, $options);
}

/**
 * Valida usuario usando un archivo JSON local (Ideal para pruebas rápidas).
 */
function validateUserJSON(string $email, string $password): array|false {
    if (!file_exists(JSON_FILE)) {
        return false;
    }
    
    $users = json_decode(file_get_contents(JSON_FILE), true);
    if (!is_array($users)) {
        return false;
    }
    
    foreach ($users as $user) {
        // Coincidencia por email y verificación segura de contraseña
        $userEmail = $user['email'] ?? $user['username'] ?? '';
        if ($userEmail === $email && password_verify($password, $user['password'])) {
            return $user;
        }
    }
    return false;
}

/**
 * Valida usuario usando MySQL con consultas preparadas (Seguro contra SQL Injection).
 */
function validateUserMySQL(string $email, string $password): array|false {
    $conn = getDBConnection();
    
    // Buscamos por correo electrónico (o username según tu esquema de base de datos)
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    
    return false;
}