<?php
require_once 'config.php';

// Configurar CORS para desarrollo
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Solo aceptar peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener datos del formulario
$data = json_decode(file_get_contents('php://input'), true);

// Si no hay JSON, intentar con POST normal
if (!$data) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
} else {
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
}

// Validar que los campos no estén vacíos
if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Usuario y contraseña son requeridos']);
    exit;
}

// Validar credenciales según la configuración
if (USE_JSON) {
    $user = validateUserJSON($username, $password);
} else {
    $user = validateUserMySQL($username, $password);
}

if ($user) {
    // Iniciar sesión
    $_SESSION['user_id'] = $user['id'] ?? md5($username);
    $_SESSION['username'] = $user['username'];
    $_SESSION['login_time'] = time();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Inicio de sesión exitoso',
        'redirect' => 'dashboard.php',
        'user' => [
            'username' => $user['username']
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Usuario o contraseña incorrectos'
    ]);
}
?>