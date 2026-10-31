import java.util.Objects;

public final class Cliente {
    private final String idCliente;
    private final String nombre;
    private final String cedulaIdentidad;
    private final String telefono;
    private final String domicilio;

    public Cliente(
        String idCliente,
        String nombre,
        String cedulaIdentidad,
        String telefono,
        String domicilio
    ) {
        this.idCliente = textoRequerido(idCliente, "idCliente", 10);
        this.nombre = textoRequerido(nombre, "nombre", 100);
        this.cedulaIdentidad = textoRequerido(cedulaIdentidad, "cedulaIdentidad", 20);
        this.telefono = textoOpcional(telefono, "telefono", 20);
        this.domicilio = domicilio;
    }

    public String getIdCliente() { return idCliente; }
    public String getNombre() { return nombre; }
    public String getCedulaIdentidad() { return cedulaIdentidad; }
    public String getTelefono() { return telefono; }
    public String getDomicilio() { return domicilio; }

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
