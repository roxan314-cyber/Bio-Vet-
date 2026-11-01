document.addEventListener('DOMContentLoaded', () => {
    
    const registroForm = document.getElementById('registroForm');
    const errorMessage = document.getElementById('error-msg');
    const submitBtn = document.querySelector('.login-btn');

    // Escuchamos el evento al enviar el formulario
    registroForm.addEventListener('submit', function (e) {
        // Prevenimos que la página se recargue automáticamente
        e.preventDefault();

        // Obtención de los valores de los inputs
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();

        // Validación: Si algún campo está vacío, mostramos el aviso
        if (username === '' || password === '') {
            errorMessage.textContent = 'El campo está vacío. Por favor llena todos los campos.';
            errorMessage.style.display = 'block';
            return; // Detenemos la ejecución aquí si hay un error
        }

        // Si los datos están bien, ocultamos el mensaje de error anterior
        errorMessage.style.display = 'none';

        // Cambiamos el estado del botón a "Cargando"
        submitBtn.innerText = 'Registrando...';
        submitBtn.disabled = true;

        // Simulación de procesamiento (espera de 2 segundos)
        setTimeout(() => {
            alert(`¡Bienvenido/a, ${username}! Tu cuenta ha sido creada con éxito.`);

            // Restablecemos el botón a su estado original
            submitBtn.innerText = 'Registrarse';
            submitBtn.disabled = false;

            // Limpiamos los campos del formulario
            registroForm.reset();
        }, 2000);
    });
});