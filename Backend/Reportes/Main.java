import java.sql.SQLException;
import java.time.LocalDate;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class Main {
    public static void main(String[] args) {
        GestorReportes gestor = new GestorReportes();
        
        try {
            // Ejemplo 1: Extraer todos los reportes
            System.out.println("=== TODAS LAS CONSULTAS CLÍNICAS ===");
            List<ReporteData> todosReportes = gestor.extraerReporteCompleto();
            mostrarReportes(todosReportes);
            
            // Ejemplo 2: Consultas clínicas desde el último mes
            System.out.println("\n=== CONSULTAS DEL ÚLTIMO MES ===");
            Map<String, Object> filtros = new HashMap<>();
            filtros.put("fechaDesde", LocalDate.now().minusMonths(1));
            
            List<ReporteData> reportesFiltrados = gestor.extraerReporteFiltrado(filtros);
            mostrarReportes(reportesFiltrados);
            
            // Ejemplo 3: Paginación
            System.out.println("\n=== PRIMERA PÁGINA (5 CONSULTAS) ===");
            List<ReporteData> reportesPaginados = gestor.extraerReportePaginado(1, 5);
            mostrarReportes(reportesPaginados);
            
            // Ejemplo 4: Estadísticas
            System.out.println("\n=== ESTADÍSTICAS ===");
            ReporteEstadisticas estadisticas = gestor.obtenerEstadisticas(todosReportes);
            System.out.println(estadisticas);
            
        } catch (SQLException e) {
            System.err.println("Error al acceder a la base de datos: " + e.getMessage());
            e.printStackTrace();
        }
    }
    
    private static void mostrarReportes(List<ReporteData> reportes) {
        if (reportes.isEmpty()) {
            System.out.println("No se encontraron reportes");
            return;
        }
        
        reportes.forEach(reporte -> {
            System.out.printf(
                "Consulta: %d | Fecha: %s | Paciente: %s | Cliente: %s | Doctor: %s | "
                    + "Condición corporal: %s | Diagnóstico: %s%n",
                reporte.getIdConsulta(),
                reporte.getFecha(),
                reporte.getNombrePaciente(),
                reporte.getNombreCliente(),
                reporte.getNombreDoctor(),
                reporte.getCondicionCorporal() == null ? "N/D" : reporte.getCondicionCorporal(),
                reporte.getDiagnostico()
            );
        });
        
        System.out.println("Total de consultas: " + reportes.size());
    }
}