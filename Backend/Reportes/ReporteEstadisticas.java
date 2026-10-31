import java.util.List;
import java.util.Objects;

public class ReporteEstadisticas {
    private final int totalConsultas;
    private final int consultasConCondicion;
    private final double promedioCondicionCorporal;
    private final Integer condicionCorporalMaxima;
    private final int consultasSinDoctor;

    private ReporteEstadisticas(
        int totalConsultas,
        int consultasConCondicion,
        double promedioCondicionCorporal,
        Integer condicionCorporalMaxima,
        int consultasSinDoctor
    ) {
        this.totalConsultas = totalConsultas;
        this.consultasConCondicion = consultasConCondicion;
        this.promedioCondicionCorporal = promedioCondicionCorporal;
        this.condicionCorporalMaxima = condicionCorporalMaxima;
        this.consultasSinDoctor = consultasSinDoctor;
    }

    public static ReporteEstadisticas desde(List<ReporteData> reportes) {
        Objects.requireNonNull(reportes, "Los reportes no pueden ser null.");

        int totalWithCondition = 0;
        long totalCondition = 0;
        Integer maxCondition = null;
        int withoutDoctor = 0;

        for (ReporteData report : reportes) {
            Objects.requireNonNull(report, "La lista no puede contener reportes null.");
            Integer condition = report.getCondicionCorporal();
            if (condition != null) {
                totalWithCondition++;
                totalCondition += condition;
                maxCondition = maxCondition == null ? condition : Math.max(maxCondition, condition);
            }
            if (report.getNombreDoctor() == null) {
                withoutDoctor++;
            }
        }

        double average = totalWithCondition == 0 ? 0.0 : (double) totalCondition / totalWithCondition;
        return new ReporteEstadisticas(
            reportes.size(),
            totalWithCondition,
            average,
            maxCondition,
            withoutDoctor
        );
    }

    public int getTotalConsultas() {
        return totalConsultas;
    }

    public int getConsultasConCondicion() {
        return consultasConCondicion;
    }
    public double getPromedioCondicionCorporal() { return promedioCondicionCorporal; }
    public Integer getCondicionCorporalMaxima() { return condicionCorporalMaxima; }
    public int getConsultasSinDoctor() { return consultasSinDoctor; }

    @Override
    public String toString() {
        return String.format(
            "Estadísticas clínicas: consultas=%d, condición evaluada=%d, promedio corporal=%.2f, "
                + "máxima=%s, sin doctor asignado=%d",
            totalConsultas,
            consultasConCondicion,
            promedioCondicionCorporal,
            condicionCorporalMaxima == null ? "N/D" : condicionCorporalMaxima,
            consultasSinDoctor
        );
    }
}
