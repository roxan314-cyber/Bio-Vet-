-- Crea la tabla para las mascotas, la cual requiere que exista un cliente primero
CREATE TABLE Pacientes (
    -- Código identificador único para cada mascota (Llave Primaria)
    id_paciente VARCHAR(10) PRIMARY KEY,
    -- Nombre del animal, campo obligatorio
    nombre_paciente VARCHAR(50) NOT NULL,
    -- Raza del animal (ej. Poodle, Mestizo, Rottweiler)
    raza VARCHAR(50),
    -- Sexo de la mascota (Macho, Hembra, Macho Castrado, etc.)
    sexo VARCHAR(10),
    -- Edad de la mascota expresada en texto (ej. '3 Meses', '5 Años')
    edad VARCHAR(30),
    -- Peso corporal en kilogramos, estructurado para admitir hasta 3 enteros y 2 decimales (ej. 145.50)
    peso_kg DECIMAL(5,2),
    -- Campo para almacenar el código del dueño de esta mascota
    id_cliente VARCHAR(10),
    -- Convierte el campo id_cliente en una llave foránea que apunta directamente a la tabla Clientes
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente) 
        -- Si se elimina un cliente, se borran automáticamente todas sus mascotas vinculadas
        ON DELETE CASCADE 
        -- Si el ID del cliente cambia, se actualiza automáticamente en la tabla de mascotas
        ON UPDATE CASCADE
);