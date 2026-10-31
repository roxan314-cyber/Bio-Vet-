CREATE DATABASE IF NOT EXISTS tu_base_datos;
USE tu_base_datos;

CREATE TABLE IF NOT EXISTS reportes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE
);

-- Datos de ejemplo
INSERT INTO reportes (nombre, valor, fecha_creacion, activo) VALUES
('Reporte Ventas', 1500.50, '2024-01-15 10:30:00', true),
('Reporte Inventario', 2300.75, '2024-01-20 14:45:00', true),
('Reporte Usuarios', 500.00, '2024-01-25 09:15:00', false),
('Reporte Productos', 3200.00, '2024-02-01 16:20:00', true);