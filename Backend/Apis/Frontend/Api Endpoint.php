<?php
// api/me.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/jwt_helper.php';

$token = obtenerTokenBearer();

if (!$token) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Token no proporcionado. Debes enviar un token Bearer en el header Authorization.'
    ]);
    exit;
}

$payload = verificarJWT($token);

if (!$payload) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Token inválido o expirado. Por favor, inicia sesión nuevamente.'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, name, email, rol, telefono, ultimo_acceso, created_at FROM users WHERE id = ?");
    $stmt->execute([$payload['user_id']]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'mensaje' => 'Usuario no encontrado'
        ]);
        exit;
    }
    
    $updateStmt = $pdo->prepare("UPDATE users SET ultimo_acceso = NOW() WHERE id = ?");
    $updateStmt->execute([$usuario['id']]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'mensaje' => 'Usuario autenticado correctamente',
        'usuario' => [
            'id' => $usuario['id'],
            'name' => $usuario['name'],
            'email' => $usuario['email'],
            'rol' => $usuario['rol'],
            'telefono' => $usuario['telefono'],
            'ultimo_acceso' => $usuario['ultimo_acceso'],
            'fecha_registro' => $usuario['created_at']
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error interno del servidor',
        'debug' => $e->getMessage() // Solo en desarrollo
    ]);
}