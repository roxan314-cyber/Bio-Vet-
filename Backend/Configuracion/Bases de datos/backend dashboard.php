<?php
require_once 'config.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    header('Location: ../index.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .dashboard {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .welcome {
            color: #667eea;
            margin-bottom: 30px;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
        }
        
        .info {
            background: #f7f7f7;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h1>Bienvenido</h1>
        <p class="welcome">Has iniciado sesión exitosamente</p>
        
        <div class="info">
            <p><strong>Usuario:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            <p><strong>Hora de inicio:</strong> <?php echo date('Y-m-d H:i:s', $_SESSION['login_time']); ?></p>
        </div>
        
        <button class="logout-btn" onclick="window.location.href='logout.php'">Cerrar Sesión</button>
    </div>
</body>
</html>