-- Crea la tabla para almacenar la información de los dueños de las mascotas
CREATE TABLE Clientes (
    -- Define el identificador único del cliente (Llave Primaria) como texto de hasta 10 caracteres
    id_cliente VARCHAR(10) PRIMARY KEY,
    -- Almacena el nombre completo del cliente, obligatorio (NOT NULL), hasta 100 caracteres
    nombre_cliente VARCHAR(100) NOT NULL,
    -- Almacena el documento de identidad, obligatorio y no permite duplicados (UNIQUE)
    cedula_identidad VARCHAR(20) NOT NULL UNIQUE,
    -- Almacena el número telefónico de contacto (acepta hasta 20 caracteres por códigos de área)
    telefono VARCHAR(20),
    -- Almacena la dirección de vivienda del cliente sin límite estricto de caracteres
    domicilio TEXT
);