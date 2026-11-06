-- Crea la tabla central que unifica el historial clínico y conecta las 3 tablas anteriores
CREATE TABLE Historias_Clinicas (
    -- Código de consulta numérico que se incrementa solo (1, 2, 3...) con cada nuevo registro
    id_consulta INT AUTO_INCREMENT PRIMARY KEY,
    -- Almacena la fecha exacta en la que se realiza la atención médica (Año-Mes-Día)
    fecha DATE NOT NULL,
    -- Almacena la hora exacta en la que se atiende la consulta médica
    hora TIME,
    -- Campo para registrar qué cliente asistió a la consulta
    id_cliente VARCHAR(10) NOT NULL,
    -- Campo para registrar qué mascota fue la que recibió la atención
    id_paciente VARCHAR(10) NOT NULL,
    -- Campo para registrar qué médico veterinario atendió el caso
    id_doctor VARCHAR(10) NOT NULL,
    -- Texto amplio para detallar la razón por la que trajeron a la mascota
    motivo_consulta TEXT NOT NULL,
    -- Antecedentes médicos previos, patologías pasadas o cirugías
    anamnesis TEXT,
    -- Temperatura corporal tomada durante la revisión (°C)
    temperatura_c DECIMAL(4,1),
    -- Frecuencia cardíaca en latidos por minuto (LPM)
    frecuencia_cardiaca_bpm INT,
    -- Frecuencia respiratoria en respiraciones por minuto (RPM)
    frecuencia_respiratoria_rpm INT,
    -- Tiempo de llenado capilar medido en segundos
    tiempo_llenado_capilar_seg DECIMAL(3,1),
    -- Estado de las mucosas (ej. Rosadas, Pálidas, Ictéricas, Cianóticas)
    estado_mucosas VARCHAR(30),
    -- Porcentaje estimado de deshidratación del paciente
    porcentaje_deshidratacion DECIMAL(4,1),
    -- Guarda la evaluación del estado físico del animal (generalmente del 1 al 5)
    condicion_corporal INT,
    -- Peso actual registrado durante la consulta en kilogramos
    peso_actual_kg DECIMAL(5,2),
    -- Hallazgos del examen físico sistemático de la mascota
    examen_fisico_detallado TEXT,
    -- Diagnóstico presuntivo o sospecha clínica inicial
    diagnostico_presuntivo TEXT,
    -- Texto amplio para plasmar los diagnósticos (Presuntivo, Diferencial o Definitivo)
    diagnostico_dx TEXT NOT NULL,
    -- Exámenes complementarios solicitados (Ej. Hemograma, Ecografía, Rayos X)
    pruebas_laboratorio_solicitadas TEXT,
    -- Resultados de las pruebas de laboratorio o imagenología realizada
    resultados_pruebas TEXT,
    -- Texto amplio para transcribir el tratamiento, recetas y órdenes de la sección "Plan de Acción"
    plan_accion TEXT NOT NULL,
    -- Medicamentos o principios activos recetados
    tratamiento_farmacologico TEXT,
    -- Dosis, vía de administración y frecuencia asignada
    posologia_indicada TEXT,
    -- Indicaciones nutricionales o dieta especial para la recuperación
    indicaciones_dieta TEXT,
    -- Procedimientos quirúrgicos o intervenciones menores realizadas
    procedimientos_realizados TEXT,
    -- Pronóstico médico del paciente (ej. Favorable, Reservado, Grave)
    pronostico VARCHAR(50),
    -- Recomendaciones médicas y cuidados en el hogar
    indicaciones_casa TEXT,
    -- Fecha programada para la próxima consulta o revisión de control
    fecha_proximo_control DATE,
    -- Campo para indicar si el paciente requiere hospitalización inmediata
    requiere_hospitalizacion BOOLEAN DEFAULT FALSE,
    -- Notas internas del médico o comentarios adicionales
    observaciones_internas TEXT,
    -- Campo de auditoría: Fecha y hora exacta de creación del registro
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Relaciona esta consulta con un cliente válido de la tabla Clientes
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente)
        ON DELETE CASCADE ON UPDATE CASCADE,
    -- Relaciona esta consulta con un paciente válido de la tabla Pacientes
    FOREIGN KEY (id_paciente) REFERENCES Pacientes(id_paciente)
        ON DELETE CASCADE ON UPDATE CASCADE,
    -- Relaciona esta consulta con un doctor válido de la tabla Doctores
    FOREIGN KEY (id_doctor) REFERENCES Doctores(id_doctor)
        ON DELETE RESTRICT ON UPDATE CASCADE
);