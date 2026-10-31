import java.time.LocalDate;

public class ReporteData {
    private final int idConsulta;
    private final LocalDate fecha;
    private final String nombreCliente;
    private final String nombrePaciente;
    private final String nombreDoctor;
    private final String motivoConsulta;
    private final Integer condicionCorporal;
    private final String diagnostico;
    private final String planAccion;

    public ReporteData(
        int idConsulta,
        LocalDate fecha,
        String nombreCliente,
        String nombrePaciente,
        String nombreDoctor,
        String motivoConsulta,
        Integer condicionCorporal,
        String diagnostico,
        String planAccion
    ) {
        this.idConsulta = idConsulta;
        this.fecha = fecha;
        this.nombreCliente = nombreCliente;
        this.nombrePaciente = nombrePaciente;
        this.nombreDoctor = nombreDoctor;
        this.motivoConsulta = motivoConsulta;
        this.condicionCorporal = condicionCorporal;
        this.diagnostico = diagnostico;
        this.planAccion = planAccion;
    }

    public int getIdConsulta() {
        return idConsulta;
    }

    public LocalDate getFecha() {
        return fecha;
    }
    public String getNombreCliente() { return nombreCliente; }
    public String getNombrePaciente() { return nombrePaciente; }
    public String getNombreDoctor() { return nombreDoctor; }
    public String getMotivoConsulta() { return motivoConsulta; }
    public Integer getCondicionCorporal() { return condicionCorporal; }
    public String getDiagnostico() { return diagnostico; }
    public String getPlanAccion() { return planAccion; }

    @Override
    public String toString() {
        return "ReporteData{"
            + "idConsulta=" + idConsulta
            + ", fecha=" + fecha
            + ", cliente='" + nombreCliente + '\''
            + ", paciente='" + nombrePaciente + '\''
            + ", doctor='" + nombreDoctor + '\''
            + ", condicionCorporal=" + condicionCorporal
            + '}';
    }
}
