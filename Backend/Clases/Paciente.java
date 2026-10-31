import java.math.BigDecimal;
import java.util.Objects;

public final class Paciente {
    private final String idPaciente;
    private final String nombre;
    private final String raza;
    private final String sexo;
    private final String edad;
    private final BigDecimal pesoKg;
    private final String idCliente;

    public Paciente(
        String idPaciente,
        String nombre,
        String raza,
        String sexo,
        String edad,
        BigDecimal pesoKg,
        String idCliente
    ) {
        this.idPaciente = textoRequerido(idPaciente, "idPaciente", 10);
        this.nombre = textoRequerido(nombre, "nombre", 50);
        this.raza = textoOpcional(raza, "raza", 50);
        this.sexo = textoOpcional(sexo, "sexo", 10);
        this.edad = textoOpcional(edad, "edad", 30);
        this.pesoKg = validarPeso(pesoKg);
        this.idCliente = textoOpcional(idCliente, "idCliente", 10);
    }

    public String getIdPaciente() { return idPaciente; }
    public String getNombre() { return nombre; }
    public String getRaza() { return raza; }
    public String getSexo() { return sexo; }
    public String getEdad() { return edad; }
    public BigDecimal getPesoKg() { return pesoKg; }
    public String getIdCliente() { return idCliente; }

    private static BigDecimal validarPeso(BigDecimal weight) {
        if (weight == null) {
            return null;
        }
        if (weight.signum() < 0 || weight.precision() > 5 || weight.scale() > 2) {
            throw new IllegalArgumentException("pesoKg no puede ser negativo y debe caber en DECIMAL(5,2).");
        }
        return weight;
    }

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
