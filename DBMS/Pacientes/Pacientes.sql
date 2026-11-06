-- Crea la tabla para las mascotas, la cual requiere que exista un cliente primero
CREATE TABLE Pacientes (
    -- Código identificador único para cada mascota (Llave Primaria)
    id_paciente VARCHAR(10) PRIMARY KEY,
    -- Nombre del animal, campo obligatorio
    nombre_paciente VARCHAR(50) NOT NULL,
    -- Especie de la mascota (ej. Perro, Gato, Ave, Reptil)
    especie VARCHAR(30) NOT NULL,
    -- Raza del animal (ej. Poodle, Mestizo, Rottweiler)
    raza VARCHAR(50),
    -- Color o patrón de pelaje/plumaje de la mascota
    color_pelaje VARCHAR(50),
    -- Tipo de pelaje (ej. Corto, Largo, Duro, Rizado)
    tipo_pelaje VARCHAR(30),
    -- Sexo de la mascota (Macho, Hembra, Macho Castrado, etc.)
    sexo VARCHAR(20),
    -- Fecha exacta de nacimiento de la mascota si se conoce
    fecha_nacimiento DATE,
    -- Edad de la mascota expresada en texto (ej. '3 Meses', '5 Años')
    edad VARCHAR(30),
    -- Peso corporal en kilogramos, estructurado para admitir hasta 3 enteros y 2 decimales (ej. 145.50)
    peso_kg DECIMAL(5,2),
    -- Altura o alzada de la mascota medida en centímetros
    altura_cm DECIMAL(5,2),
    -- Indica si la mascota posee un microchip de identificación oficial
    numero_microchip VARCHAR(50) UNIQUE,
    -- Ubicación anatómica donde fue implantado el microchip
    ubicacion_microchip VARCHAR(50),
    -- Número de tatuaje de identificación si aplica
    numero_tatuaje VARCHAR(50),
    -- Indica si la mascota está esterilizada o castrada
    esterilizado BOOLEAN DEFAULT FALSE,
    -- Fecha en que se realizó la cirugía de esterilización
    fecha_esterilizacion DATE,
    -- Almacena alergias conocidas a medicamentos o alimentos
    alergias_conocidas TEXT,
    -- Almacena condiciones preexistentes o enfermedades crónicas
    condiciones_medicas TEXT,
    -- Registro de dieta o tipo de alimentación habitual
    dieta_habitual TEXT,
    -- Marca o tipo de alimento comercial que consume
    marca_alimento VARCHAR(100),
    -- Almacena marcas de nacimiento, cicatrices u otras señas particulares
    senas_particulares TEXT,
    -- Comportamiento o temperamento (ej. Sociable, Miedoso, Agresivo)
    temperamento VARCHAR(50) DEFAULT 'Sociable',
    -- Estado de vacunación actual de la mascota (Al día, Incompleto, Pendiente)
    estado_vacunacion VARCHAR(30) DEFAULT 'Pendiente',
    -- Estado del esquema de desparasitación interna y externa
    estado_desparasitacion VARCHAR(30) DEFAULT 'Pendiente',
    -- Fecha de la última desparasitación registrada
    fecha_ultima_desparasitacion DATE,
    -- Fecha de la última vacuna aplicada a la mascota
    fecha_ultima_vacuna DATE,
    -- Indica si el paciente requiere manejo especial o bozal
    requiere_bozal BOOLEAN DEFAULT FALSE,
    -- Almacena el número de póliza de seguro médico si aplica
    numero_seguro VARCHAR(50),
    -- Nombre o contacto de la empresa aseguradora
    compania_seguro VARCHAR(100),
    -- Foto de perfil o imagen de la mascota (URL o ruta de archivo)
    url_fotografia VARCHAR(255),
    -- Observaciones generales o notas de atención especial
    notas_adicionales TEXT,
    -- Estado actual del paciente (Activo, Fallecido, Extraviado, Adopción)
    estado_paciente VARCHAR(20) DEFAULT 'Activo',
    -- Campo para almacenar el código del dueño de esta mascota
    id_cliente VARCHAR(10) NOT NULL,
    -- Campo de auditoría: Fecha y hora exacta de registro de la mascota
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Campo de auditoría: Fecha de última actualización de los datos
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Convierte el campo id_cliente en una llave foránea que apunta directamente a la tabla Clientes
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente) 
        -- Si se elimina un cliente, se borran automáticamente todas sus mascotas vinculadas
        ON DELETE CASCADE 
        -- Si el ID del cliente cambia, se actualiza automáticamente en la tabla de mascotas
        ON UPDATE CASCADE
);