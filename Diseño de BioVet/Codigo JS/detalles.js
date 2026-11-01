document.addEventListener('DOMContentLoaded', function () {

    const parametros = new URLSearchParams(window.location.search);
    const servicioSeleccionado = parametros.get('servicio');

    const titulo = document.getElementById('servicio-titulo');
    const descripcion = document.getElementById('servicio-descripcion');
    const beneficios = document.getElementById('servicio-beneficios');
    const icono = document.getElementById('servicio-icono');

    if (servicioSeleccionado === 'consultas') {
        titulo.textContent = 'Consultas Veterinarias';
        descripcion.textContent = 'Ofrecemos atención médica integral para evaluar la salud de tu mascota con exámenes físicos completos.';
        icono.className = 'fa-solid fa-stethoscope';
        
        beneficios.innerHTML = 
            '<li>Evaluación física general completa</li>' +
            '<li>Diagnóstico de patologías agudas y crónicas</li>' +
            '<li>Asesoramiento en nutrición y cuidados</li>';

    } else if (servicioSeleccionado === 'vacunacion') {
        titulo.textContent = 'Vacunación y Desparasitación';
        descripcion.textContent = 'Protege a tu mascota con esquemas completos de vacunación y control de parásitos.';
        icono.className = 'fa-solid fa-syringe';
        
        beneficios.innerHTML = 
            '<li>Planes de vacunación para cachorros y adultos</li>' +
            '<li>Control de parásitos internos y externos</li>' +
            '<li>Entrega de carnet oficial de vacunación</li>';

    } else if (servicioSeleccionado === 'cirugias') {
        titulo.textContent = 'Cirugías Médicas';
        descripcion.textContent = 'Intervenciones quirúrgicas preventivas y de urgencia con equipamiento moderno.';
        icono.className = 'fa-solid fa-user-md';
        
        beneficios.innerHTML = 
            '<li>Esterilizaciones y castraciones</li>' +
            '<li>Cirugías de tejidos blandos</li>' +
            '<li>Monitoreo y recuperación asistida</li>';

    } else if (servicioSeleccionado === 'diagnostico') {
        titulo.textContent = 'Diagnóstico por Imagen';
        descripcion.textContent = 'Exámenes de radiografía digital y ecografía de alta resolución.';
        icono.className = 'fa-solid fa-x-ray';
        
        beneficios.innerHTML = 
            '<li>Radiología digital rápida</li>' +
            '<li>Ecografías abdominales y cardíacas</li>' +
            '<li>Detección temprana de anomalías</li>';

    } else if (servicioSeleccionado === 'laboratorio') {
        titulo.textContent = 'Laboratorio Clínico';
        descripcion.textContent = 'Análisis de sangre y exámenes generales con entrega rápida de resultados.';
        icono.className = 'fa-solid fa-microscope';
        
        beneficios.innerHTML = 
            '<li>Hemogramas y perfiles metabólicos</li>' +
            '<li>Análisis de muestra de orina y heces</li>' +
            '<li>Pruebas de enfermedades infecciosas</li>';

    } else if (servicioSeleccionado === 'emergencias') {
        titulo.textContent = 'Emergencias 24/7';
        descripcion.textContent = 'Atención médica prioritaria a cualquier hora del día o de la noche.';
        icono.className = 'fa-solid fa-truck-medical';
        
        beneficios.innerHTML = 
            '<li>Atención disponible los 365 días del año</li>' +
            '<li>Unidad de cuidados intensivos</li>' +
            '<li>Tratamiento inmediato de trauma y urgencias</li>';

    } else {
        titulo.textContent = 'Servicio no encontrado';
        descripcion.textContent = 'Por favor selecciona un servicio válido desde la página principal.';
        beneficios.innerHTML = '';
    }
});