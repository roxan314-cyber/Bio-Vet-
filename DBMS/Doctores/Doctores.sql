-- Crea la tabla para registrar al personal médico de la clínica
CREATE TABLE Doctores (
    -- Código identificador único para cada médico veterinario (Llave Primaria)
    id_doctor VARCHAR(10) PRIMARY KEY,
    -- Primer Nombre del doctor, campo obligatorio
    nombre_doctor VARCHAR(100) NOT NULL,
     -- Segundo Nombre del doctor, campo obligatorio
    nombre_doctor VARCHAR(100) NOT NULL,
    -- Almacena el apellido paterno del doctor para un registro más formal
    apellido_paterno VARCHAR(50) NOT NULL,
    -- Almacena el apellido materno del doctor
    apellido_materno VARCHAR(50),
    -- Documento de identidad o cédula del médico para fines legales y de contratación
    cedula_identidad VARCHAR(20) NOT NULL UNIQUE,
    -- Especialidad del médico. Si se deja vacío, por defecto asigna 'Medicina Veterinaria'
    especialidad VARCHAR(50) DEFAULT 'Medicina Veterinaria', 
    -- Almacena el registro legal o número de colegiatura del profesional
    numero_colegiado VARCHAR(30) NOT NULL UNIQUE,
    -- Teléfono celular o local del médico para contacto interno
    telefono VARCHAR(20),
    -- Almacena un número telefónico alternativo o de emergencia del doctor
    telefono_emergencia VARCHAR(20),
    -- Correo electrónico institucional o personal del doctor
    correo VARCHAR(100) UNIQUE,
    -- Dirección de domicilio actual del doctor
    domicilio TEXT,
    -- Define la jornada laboral asignada (ej. Mañana, Tarde, Noche)
    turno VARCHAR(20) DEFAULT 'Mañana',
    -- Días de la semana asignados para laborar en la clínica
    dias_laborales VARCHAR(100) DEFAULT 'Lunes a Viernes',
    -- Fecha de contratación o inicio de labores del médico en la clínica
    fecha_contratacion DATE,
    -- Salario base asignado al profesional
    salario_base DECIMAL(10, 2),
    -- Estado laboral. Si no se especifica, el sistema lo registra como 'Activo'
    estado VARCHAR(15) DEFAULT 'Activo',
    -- Campo de auditoría: Fecha y hora en que se registró al doctor en el sistema
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Campo de auditoría: Fecha de la última actualización de los datos del médico
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);