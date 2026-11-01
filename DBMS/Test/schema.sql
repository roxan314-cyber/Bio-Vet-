CREATE DATABASE IF NOT EXISTS biovet_test_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE biovet_test_db;

CREATE TABLE IF NOT EXISTS Clientes (
    id_cliente VARCHAR(10) NOT NULL,
    nombre_cliente VARCHAR(100) NOT NULL,
    cedula_identidad VARCHAR(20) NOT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    domicilio TEXT DEFAULT NULL,
    PRIMARY KEY (id_cliente),
    UNIQUE KEY cedula_identidad (cedula_identidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Doctores (
    id_doctor VARCHAR(10) NOT NULL,
    nombre_doctor VARCHAR(100) NOT NULL,
    especialidad VARCHAR(50) DEFAULT 'Medicina Veterinaria',
    numero_colegiado VARCHAR(30) DEFAULT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    correo VARCHAR(100) DEFAULT NULL,
    turno VARCHAR(20) DEFAULT NULL,
    estado VARCHAR(15) DEFAULT 'Activo',
    PRIMARY KEY (id_doctor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Pacientes (
    id_paciente VARCHAR(10) NOT NULL,
    nombre_paciente VARCHAR(50) NOT NULL,
    raza VARCHAR(50) DEFAULT NULL,
    sexo VARCHAR(10) DEFAULT NULL,
    edad VARCHAR(30) DEFAULT NULL,
    peso_kg DECIMAL(5,2) DEFAULT NULL,
    id_cliente VARCHAR(10) DEFAULT NULL,
    PRIMARY KEY (id_paciente),
    KEY id_cliente (id_cliente),
    CONSTRAINT pacientes_test_ibfk_1
        FOREIGN KEY (id_cliente) REFERENCES Clientes (id_cliente)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Historias_Clinicas (
    id_consulta INT NOT NULL AUTO_INCREMENT,
    fecha DATE NOT NULL,
    id_cliente VARCHAR(10) DEFAULT NULL,
    id_paciente VARCHAR(10) DEFAULT NULL,
    id_doctor VARCHAR(10) DEFAULT NULL,
    motivo_consulta TEXT DEFAULT NULL,
    condicion_corporal INT DEFAULT NULL,
    diagnostico_dx TEXT DEFAULT NULL,
    plan_accion TEXT DEFAULT NULL,
    PRIMARY KEY (id_consulta),
    KEY id_cliente (id_cliente),
    KEY id_paciente (id_paciente),
    KEY id_doctor (id_doctor),
    CONSTRAINT historias_clinicas_test_ibfk_1
        FOREIGN KEY (id_cliente) REFERENCES Clientes (id_cliente),
    CONSTRAINT historias_clinicas_test_ibfk_2
        FOREIGN KEY (id_paciente) REFERENCES Pacientes (id_paciente),
    CONSTRAINT historias_clinicas_test_ibfk_3
        FOREIGN KEY (id_doctor) REFERENCES Doctores (id_doctor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
