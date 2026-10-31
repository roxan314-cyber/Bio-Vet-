import java.sql.Connection;
import java.sql.Date;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.time.LocalDate;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.Objects;
import java.util.Set;

public class GestorReportes {
    private static final int MAX_PAGE_SIZE = 500;
    private static final Set<String> ALLOWED_FILTERS = Set.of(
        "fechaDesde",
        "fechaHasta",
        "idCliente",
        "idPaciente",
        "idDoctor",
        "condicionCorporalMinima"
    );
    private static final String SELECT_REPORTS = """
        SELECT hc.id_consulta, hc.fecha,
               c.nombre_cliente, p.nombre_paciente, d.nombre_doctor,
               hc.motivo_consulta, hc.condicion_corporal,
               hc.diagnostico_dx, hc.plan_accion
        FROM historias_clinicas hc
        LEFT JOIN clientes c ON c.id_cliente = hc.id_cliente
        LEFT JOIN pacientes p ON p.id_paciente = hc.id_paciente
        LEFT JOIN doctores d ON d.id_doctor = hc.id_doctor
        """;

    public List<ReporteData> extraerReporteCompleto() throws SQLException {
        return ejecutarReporte(SELECT_REPORTS + " ORDER BY hc.fecha DESC, hc.id_consulta DESC", List.of());
    }

    public List<ReporteData> extraerReporteFiltrado(Map<String, Object> filtros) throws SQLException {
        Objects.requireNonNull(filtros, "Los filtros no pueden ser null.");

        StringBuilder query = new StringBuilder(SELECT_REPORTS).append(" WHERE 1 = 1");
        List<Object> parametros = new ArrayList<>();

        for (String filter : filtros.keySet()) {
            if (filter == null || !ALLOWED_FILTERS.contains(filter)) {
                throw new IllegalArgumentException("Filtro de reporte no admitido: " + filter);
            }
        }

        appendDateFilter(filtros, "fechaDesde", "hc.fecha >= ?", query, parametros);
        appendDateFilter(filtros, "fechaHasta", "hc.fecha <= ?", query, parametros);
        appendIdFilter(filtros, "idCliente", "hc.id_cliente", query, parametros);
        appendIdFilter(filtros, "idPaciente", "hc.id_paciente", query, parametros);
        appendIdFilter(filtros, "idDoctor", "hc.id_doctor", query, parametros);
        appendConditionFilter(filtros, query, parametros);

        LocalDate from = (LocalDate) filtros.get("fechaDesde");
        LocalDate to = (LocalDate) filtros.get("fechaHasta");
        if (from != null && to != null && from.isAfter(to)) {
            throw new IllegalArgumentException("fechaDesde no puede ser posterior a fechaHasta.");
        }

        query.append(" ORDER BY hc.fecha DESC, hc.id_consulta DESC");
        return ejecutarReporte(query.toString(), parametros);
    }

    public List<ReporteData> extraerReportePaginado(int pagina, int tamanioPagina) throws SQLException {
        if (pagina < 1) {
            throw new IllegalArgumentException("La página debe ser un entero mayor que cero.");
        }
        if (tamanioPagina < 1 || tamanioPagina > MAX_PAGE_SIZE) {
            throw new IllegalArgumentException(
                "El tamaño de página debe estar entre 1 y " + MAX_PAGE_SIZE + "."
            );
        }

        long offset = Math.multiplyExact((long) pagina - 1, tamanioPagina);
        String query = SELECT_REPORTS
            + " ORDER BY hc.fecha DESC, hc.id_consulta DESC LIMIT ? OFFSET ?";

        try (Connection connection = DatabaseConfig.getConnection();
             PreparedStatement statement = connection.prepareStatement(query)) {
            statement.setInt(1, tamanioPagina);
            statement.setLong(2, offset);
            try (ResultSet resultSet = statement.executeQuery()) {
                return mapearReportes(resultSet);
            }
        }
    }

    public ReporteEstadisticas obtenerEstadisticas(List<ReporteData> reportes) {
        return ReporteEstadisticas.desde(Objects.requireNonNull(reportes, "Los reportes no pueden ser null."));
    }

    private List<ReporteData> ejecutarReporte(String query, List<Object> parametros) throws SQLException {
        try (Connection connection = DatabaseConfig.getConnection();
             PreparedStatement statement = connection.prepareStatement(query)) {
            for (int index = 0; index < parametros.size(); index++) {
                statement.setObject(index + 1, parametros.get(index));
            }
            try (ResultSet resultSet = statement.executeQuery()) {
                return mapearReportes(resultSet);
            }
        }
    }

    private List<ReporteData> mapearReportes(ResultSet resultSet) throws SQLException {
        List<ReporteData> reportes = new ArrayList<>();
        while (resultSet.next()) {
            int conditionValue = resultSet.getInt("condicion_corporal");
            Integer condition = resultSet.wasNull() ? null : conditionValue;
            Date consultationDate = resultSet.getDate("fecha");

            reportes.add(new ReporteData(
                resultSet.getInt("id_consulta"),
                consultationDate == null ? null : consultationDate.toLocalDate(),
                resultSet.getString("nombre_cliente"),
                resultSet.getString("nombre_paciente"),
                resultSet.getString("nombre_doctor"),
                resultSet.getString("motivo_consulta"),
                condition,
                resultSet.getString("diagnostico_dx"),
                resultSet.getString("plan_accion")
            ));
        }
        return reportes;
    }

    private void appendDateFilter(
        Map<String, Object> filtros,
        String key,
        String condition,
        StringBuilder query,
        List<Object> parametros
    ) {
        Object value = filtros.get(key);
        if (value == null) {
            return;
        }
        if (!(value instanceof LocalDate date)) {
            throw new IllegalArgumentException(key + " debe ser una fecha LocalDate.");
        }
        query.append(" AND ").append(condition);
        parametros.add(Date.valueOf(date));
    }

    private void appendIdFilter(
        Map<String, Object> filtros,
        String key,
        String column,
        StringBuilder query,
        List<Object> parametros
    ) {
        Object value = filtros.get(key);
        if (value == null) {
            return;
        }
        if (!(value instanceof String id) || id.isBlank() || id.length() > 10) {
            throw new IllegalArgumentException(key + " debe contener un identificador de 1 a 10 caracteres.");
        }
        query.append(" AND ").append(column).append(" = ?");
        parametros.add(id);
    }

    private void appendConditionFilter(
        Map<String, Object> filtros,
        StringBuilder query,
        List<Object> parametros
    ) {
        Object value = filtros.get("condicionCorporalMinima");
        if (value == null) {
            return;
        }
        if (!(value instanceof Integer condition) || condition < 1 || condition > 5) {
            throw new IllegalArgumentException("condicionCorporalMinima debe ser un entero entre 1 y 5.");
        }
        query.append(" AND hc.condicion_corporal >= ?");
        parametros.add(condition);
    }
}
