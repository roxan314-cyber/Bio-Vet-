// Iniciamos ScrollReveal con reset: true para que se repita al subir y bajar
window.sr = ScrollReveal({ reset: true });

// Animación para las tarjetas de "Quiénes somos" (Aparecen desde abajo)
sr.reveal('.card', {
    duration: 1000,
    origin: 'bottom',
    distance: '50px',
    delay: 100
});

// Animación para el título principal y textos
sr.reveal('.textos-header h1, .titulo-seccion', {
    duration: 1200,
    origin: 'top',
    distance: '40px'
});

// Animación para las tarjetas de servicios (Aparecen en cascada / intervalo)
sr.reveal('.servicio-card', {
    duration: 1000,
    origin: 'bottom',
    distance: '50px',
    interval: 150 // Hace que aparezcan una tras otra
});