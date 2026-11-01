document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const errorMessage = document.getElementById('errorMessage');

    loginForm.addEventListener('submit', function (e) {
        e.preventDefault();

        // Obtener los valores de usuario y contraseña
        submitBtn.innerText = 'Iniciando...';
        submitBtn.disabled = true;

        const username = document.getElementById('username').value;

        // Simulación de validación
        setTimeout(() => {
            alert(`Bienvenido, ${username}!`);

            // Restablecer el botón después de la simulación
            submitBtn.innerText = 'Iniciar Sesión';
            submitBtn.disabled = false;

            // Redirige al usuario a otra página o realizar otras acciones
            loginForm.reset();
        }, 2000);
    });
});