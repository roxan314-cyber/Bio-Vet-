-- Crea la tabla para registrar al personal médico de la clínica
CREATE TABLE Doctores (
    -- Código identificador único para cada médico veterianario (Llave Primaria)
    id_doctor VARCHAR(10) PRIMARY KEY,
    -- Nombre y apellido del doctor, campo obligatorio
    nombre_doctor VARCHAR(100) NOT NULL,
    -- Especialidad del médico. Si se deja vacío, por defecto asigna 'Medicina Veterinaria'
    especialidad VARCHAR(50) DEFAULT 'Medicina Veterinaria',
    -- Almacena el registro legal o número de colegiatura del profesional
    numero_colegiado VARCHAR(30),
    -- Teléfono celular o local del médico para contacto interno
    telefono VARCHAR(20),
    -- Correo electrónico institucional o personal del doctor
    correo VARCHAR(100),
    -- Define la jornada laboral asignada (ej. Mañana, Tarde, Noche)
    turno VARCHAR(20),
    -- Estado laboral. Si no se especifica, el sistema lo registra como 'Activo'
    estado VARCHAR(15) DEFAULT 'Activo'
);