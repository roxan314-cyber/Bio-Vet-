import java.time.LocalDate;
import java.util.Objects;

public final class HistoriaClinica {
    private final Integer idConsulta;
    private final LocalDate fecha;
    private final String idCliente;
    private final String idPaciente;
    private final String idDoctor;
    private final String motivoConsulta;
    private final Integer condicionCorporal;
    private final String diagnostico;
    private final String planAccion;

    public HistoriaClinica(
        Integer idConsulta,
        LocalDate fecha,
        String idCliente,
        String idPaciente,
        String idDoctor,
        String motivoConsulta,
        Integer condicionCorporal,
        String diagnostico,
        String planAccion
    ) {
        if (idConsulta != null && idConsulta < 1) {
            throw new IllegalArgumentException("idConsulta debe ser positivo cuando está definido.");
        }
        this.idConsulta = idConsulta;
        this.fecha = Objects.requireNonNull(fecha, "fecha es obligatoria.");
        this.idCliente = textoOpcional(idCliente, "idCliente");
        this.idPaciente = textoOpcional(idPaciente, "idPaciente");
        this.idDoctor = textoOpcional(idDoctor, "idDoctor");
        this.motivoConsulta = motivoConsulta;
        if (condicionCorporal != null && (condicionCorporal < 1 || condicionCorporal > 5)) {
            throw new IllegalArgumentException("condicionCorporal debe estar entre 1 y 5.");
        }
        this.condicionCorporal = condicionCorporal;
        this.diagnostico = diagnostico;
        this.planAccion = planAccion;
    }

    public Integer getIdConsulta() { return idConsulta; }
    public LocalDate getFecha() { return fecha; }
    public String getIdCliente() { return idCliente; }
    public String getIdPaciente() { return idPaciente; }
    public String getIdDoctor() { return idDoctor; }
    public String getMotivoConsulta() { return motivoConsulta; }
    public Integer getCondicionCorporal() { return condicionCorporal; }
    public String getDiagnostico() { return diagnostico; }
    public String getPlanAccion() { return planAccion; }

    private static String textoOpcional(String value, String field) {
        if (value == null) {
            return null;
        }
        String normalized = value.trim();
        if (normalized.isEmpty()) {
            return null;
        }
        if (normalized.length() > 10) {
            throw new IllegalArgumentException(field + " no puede superar 10 caracteres.");
        }
        return normalized;
    }
}
