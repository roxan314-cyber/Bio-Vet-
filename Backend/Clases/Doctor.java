import java.util.Objects;

public final class Doctor {
    private final String idDoctor;
    private final String nombre;
    private final String especialidad;
    private final String numeroColegiado;
    private final String telefono;
    private final String correo;
    private final String turno;
    private final String estado;

    public Doctor(
        String idDoctor,
        String nombre,
        String especialidad,
        String numeroColegiado,
        String telefono,
        String correo,
        String turno,
        String estado
    ) {
        this.idDoctor = textoRequerido(idDoctor, "idDoctor", 10);
        this.nombre = textoRequerido(nombre, "nombre", 100);
        this.especialidad = textoOpcional(especialidad, "especialidad", 50);
        this.numeroColegiado = textoOpcional(numeroColegiado, "numeroColegiado", 30);
        this.telefono = textoOpcional(telefono, "telefono", 20);
        this.correo = textoOpcional(correo, "correo", 100);
        this.turno = textoOpcional(turno, "turno", 20);
        this.estado = textoOpcional(estado, "estado", 15);
    }

    public String getIdDoctor() { return idDoctor; }
    public String getNombre() { return nombre; }
    public String getEspecialidad() { return especialidad; }
    public String getNumeroColegiado() { return numeroColegiado; }
    public String getTelefono() { return telefono; }
    public String getCorreo() { return correo; }
    public String getTurno() { return turno; }
    public String getEstado() { return estado; }

    private static String textoRequerido(String value, String field, int maxLength) {
        Objects.requireNonNull(value, field + " es obligatorio.");
        String normalized = value.trim();
        if (normalized.isEmpty() || normalized.length() > maxLength) {
            throw new IllegalArgumentException(field + " debe contener entre 1 y " + maxLength + " caracteres.");
        }
        return normalized;
    }

    private static String textoOpcional(String value, String field, int maxLength) {
        if (value == null) {
            return null;
        }
        String normalized = value.trim();
        if (normalized.isEmpty()) {
            return null;
        }
        if (normalized.length() > maxLength) {
            throw new IllegalArgumentException(field + " no puede superar " + maxLength + " caracteres.");
        }
        return normalized;
    }
}
