const registrationForm = document.getElementById('registroForm');

registrationForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const submitButton = registrationForm.querySelector('button[type="submit"]');
    const statusMessage = document.getElementById('error-msg');
    const formData = new FormData(registrationForm);

    submitButton.disabled = true;
    submitButton.textContent = 'Creando cuenta...';
    statusMessage.textContent = '';
    statusMessage.setAttribute('data-state', '');

    try {
        const response = await window.BioVetApi.register({
            name: formData.get('name'),
            email: formData.get('email'),
            password: formData.get('password'),
            password_confirmation: formData.get('password_confirmation')
        });
        let result;
        try {
            result = await response.json();
        } catch {
            throw new Error('El servidor devolvió una respuesta no válida.');
        }
        if (!result || typeof result !== 'object' || Array.isArray(result)) {
            throw new Error('El servidor devolvió una respuesta no válida.');
        }

        if (!response.ok) {
            const validationMessages = Object.values(result.errors || {})
                .flatMap((value) => Array.isArray(value) ? value : [value])
                .filter((value) => typeof value === 'string');
            const message = (typeof result.mensaje === 'string' && result.mensaje)
                || validationMessages.join('. ')
                || 'No se pudo crear la cuenta.';
            throw new Error(message);
        }
        if (
            !result.success
            || !result.usuario
            || typeof result.usuario !== 'object'
            || Array.isArray(result.usuario)
        ) {
            throw new Error('El servidor no confirmó la creación de la cuenta.');
        }

        statusMessage.textContent = 'Cuenta creada. Ya puedes iniciar sesión.';
        statusMessage.setAttribute('data-state', 'success');
        registrationForm.reset();
    } catch (error) {
        statusMessage.textContent = error instanceof Error && error.message !== 'Failed to fetch'
            ? error.message
            : 'No se pudo conectar con el servidor. Intenta nuevamente.';
        statusMessage.setAttribute('data-state', 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Registrarse';
    }
});
