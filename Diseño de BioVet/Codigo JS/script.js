document.addEventListener('DOMContentLoaded', () => {
    const logo = document.querySelector('.logo');
    
    if (logo) {
        logo.addEventListener('click', function (e) {
            e.preventDefault();
            document.open();

            setTimeout(function() {
                location.reload();
            }, 1250);
        });
    }

    const btnLogin = document.querySelector('.btn-login');
    
    if (btnLogin) {
        btnLogin.addEventListener('click', function(evento) {
            evento.preventDefault();
            document.open();

            setTimeout(function() {
                window.location.href = "indexlogin.html";
            }, 2000);
        });
    }
    
    const btnRegistro = document.querySelector('.btn-registro');

    if (btnRegistro) {
        btnRegistro.addEventListener('click', function(evento) {
            evento.preventDefault();
            document.open();

            setTimeout(function() {
                window.location.href = "registroweb.html";
            }, 2000);
        });
    }

    const btnServicios = document.querySelectorAll('.btn-servicio');

    btnServicios.forEach(function(boton) {
        boton.addEventListener('click', function(evento) {
            evento.preventDefault();

            const enlaceDestino = boton.getAttribute('href');
                boton.innerText = "Abriendo...";
            
                setTimeout(function() {
                    window.open(enlaceDestino, '_blank');
            }, 2000);
        });
    });

    const enlacesMenu = document.querySelectorAll('#enlaces a');
    enlacesMenu.forEach(enlace => {
        enlace.addEventListener('click', function(evento) {
            evento.preventDefault();
            
            const idDestino = this.getAttribute('href');
            const seccionDestino = document.querySelector(idDestino);

            if (seccionDestino) {
                const posicionCorregida = seccionDestino.offsetTop - 100;

                window.scrollTo({
                    top: posicionCorregida,
                    behavior: 'smooth'
                });
            }
        });
    });
});

window.sr = ScrollReveal({ reset: true });

sr.reveal('.card', {
    duration: 1000,
    origin: 'bottom',
    distance: '50px',
    delay: 100
});

sr.reveal('.textos-header h1, .titulo-seccion', {
    duration: 1200,
    origin: 'top',
    distance: '40px'
});

sr.reveal('.servicio-card', {
    duration: 1000,
    origin: 'bottom',
    distance: '50px',
    interval: 150
});