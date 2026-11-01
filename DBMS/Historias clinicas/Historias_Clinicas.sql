-- Crea la tabla central que unifica el historial clínico y conecta las 3 tablas anteriores
CREATE TABLE Historias_Clinicas (
    -- Código de consulta numérico que se incrementa solo (1, 2, 3...) con cada nuevo registro
    id_consulta INT AUTO_INCREMENT PRIMARY KEY,
    -- Almacena la fecha exacta en la que se realiza la atención médica (Año-Mes-Día)
    fecha DATE NOT NULL,
    -- Campo para registrar qué cliente asistió a la consulta
    id_cliente VARCHAR(10),
    -- Campo para registrar qué mascota fue la que recibió la atención
    id_paciente VARCHAR(10),
    -- Campo para registrar qué médico veterinario atendió el caso
    id_doctor VARCHAR(10),
    -- Texto amplio para detallar la razón por la que trajeron a la mascota
    motivo_consulta TEXT, 
    -- Guarda la evaluación del estado físico del animal (generalmente del 1 al 5)
    condicion_corporal INT,
    -- Texto amplio para plasmar los diagnósticos (Presuntivo, Diferencial o Definitivo)
    diagnostico_dx TEXT,
    -- Texto amplio para transcribir el tratamiento, recetas y órdenes de la sección "Plan de Acción"
    plan_accion TEXT,
    -- Relaciona esta consulta con un cliente válido de la tabla Clientes
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente),
    -- Relaciona esta consulta con un paciente válido de la tabla Pacientes
    FOREIGN KEY (id_paciente) REFERENCES Pacientes(id_paciente),
    -- Relaciona esta consulta con un doctor válido de la tabla Doctores
    FOREIGN KEY (id_doctor) REFERENCES Doctores(id_doctor)
);