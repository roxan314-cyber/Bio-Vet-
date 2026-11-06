-- Crea la tabla para almacenar la información de los dueños de las mascotas
CREATE TABLE Clientes (
    -- Define el identificador único del cliente (Llave Primaria) como texto de hasta 10 caracteres
    id_cliente VARCHAR(10) PRIMARY KEY,
    -- Almacena el nombre completo del cliente, obligatorio (NOT NULL), hasta 100 caracteres
    nombre_cliente VARCHAR(100) NOT NULL,
    -- Almacena el apellido paterno del cliente para mayor detalle en el registro
    apellido_paterno VARCHAR(50) NOT NULL,
    -- Almacena el apellido materno del cliente
    apellido_materno VARCHAR(50),
    -- Almacena el documento de identidad, obligatorio y no permite duplicados (UNIQUE)
    cedula_identidad VARCHAR(20) NOT NULL UNIQUE,
    -- Almacena el correo electrónico de contacto del cliente
    correo_electronico VARCHAR(100) UNIQUE,
    -- Almacena el número telefónico principal de contacto (acepta hasta 20 caracteres por códigos de área)
    telefono VARCHAR(20),
    -- Almacena un número telefónico secundario o de emergencia
    telefono_secundario VARCHAR(20),
    -- Almacena la dirección de vivienda del cliente sin límite estricto de caracteres
    domicilio TEXT,
    -- Almacena la ciudad o localidad de residencia
    ciudad VARCHAR(50),
    -- Almacena el código postal de la zona de residencia
    codigo_postal VARCHAR(10),
    -- Almacena la fecha de nacimiento del cliente para control demográfico
    fecha_nacimiento DATE,
    -- Almacena el género o sexo del cliente 
    genero VARCHAR(20),
    -- Estado de la cuenta del cliente (Activo, Inactivo, Suspendido)
    estado_cuenta VARCHAR(20) DEFAULT 'Activo',
    -- Campo de auditoría: Almacena la fecha y hora exacta en que se registró el cliente
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Campo de auditoría: Almacena la fecha de la última actualización de sus datos
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);