<?php
session_start();
require_once 'secure_connection.php';

// Configurar respuestas JSON siempre
header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener acción
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$db = new SecureDatabaseConnection();

switch($action) {
    case 'create':
        // Crear nueva mascota
        $result = $db->insertPaciente([
            'id_paciente' => generatePatientID(),
            'nombre_paciente' => $_POST['nombre'] ?? '',
            'raza' => $_POST['raza'] ?? '',
            'sexo' => $_POST['sexo'] ?? '',
            'edad' => $_POST['edad'] ?? '',
            'peso_kg' => $_POST['peso'] ?? 0,
            'id_cliente' => $_POST['id_cliente'] ?? ''
        ]);
        
        echo json_encode($result);
        break;
        
    case 'list':
        // Listar mascotas por cliente
        $pacientes = $db->getPacientesByCliente($_GET['id_cliente'] ?? '');
        echo json_encode(['success' => true, 'data' => $pacientes]);
        break;
        
    case 'search':
        // Buscar mascotas
        $results = $db->searchPacientes($_GET['keyword'] ?? '');
        echo json_encode(['success' => true, 'data' => $results]);
        break;
        
    case 'delete':
        // Eliminar mascota (requiere token)
        $token = $_POST['verification_token'] ?? '';
        $result = $db->deletePaciente($_POST['id_paciente'] ?? '', $token);
        echo json_encode(['success' => $result]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

function generatePatientID() {
    return 'PET' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
}
?>