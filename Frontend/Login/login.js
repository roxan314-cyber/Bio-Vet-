const loginForm = document.getElementById('loginForm');
let sessionWindow = null;
let sessionWindowReady = false;
let authenticatedSession = null;

function sendSessionToWindow() {
    if (!sessionWindow || sessionWindow.closed || !authenticatedSession) {
        return false;
    }

    sessionWindow.postMessage(authenticatedSession, window.location.origin);
    return true;
}

window.addEventListener('message', (event) => {
    if (
        !sessionWindow
        || event.source !== sessionWindow
        || event.origin !== window.location.origin
        || event.data?.type !== 'biovet:session-request'
    ) {
        return;
    }

    sessionWindowReady = true;
    sendSessionToWindow();
});

loginForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    sessionWindow = window.open('/', '_blank');
    sessionWindowReady = false;
    const submitButton = document.getElementById('submitBtn');
    const statusMessage = document.getElementById('errorMessage');
    const formData = new FormData(loginForm);

    submitButton.disabled = true;
    submitButton.textContent = 'Iniciando sesión...';
    statusMessage.textContent = '';

    try {
        const response = await window.BioVetApi.login({
            email: formData.get('email'),
            password: formData.get('password')
        });
        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.mensaje || 'No se pudo iniciar sesión.');
        }
        if (typeof result.token !== 'string' || !result.usuario) {
            throw new Error('El servidor no devolvió una sesión válida.');
        }

        authenticatedSession = {
            type: 'biovet:session',
            token: result.token,
            usuario: result.usuario
        };

        if (sessionWindow && !sessionWindow.closed) {
            statusMessage.textContent = 'Sesión iniciada en una nueva pestaña.';
            if (sessionWindowReady) {
                sendSessionToWindow();
            }
            return;
        }

        window.BioVetSessionStore.saveSession(result.token, result.usuario);
        window.location.assign('/');
    } catch (error) {
        if (sessionWindow && !sessionWindow.closed) {
            sessionWindow.close();
        }
        sessionWindow = null;
        statusMessage.textContent = error instanceof Error
            ? error.message
            : 'No se pudo conectar con el servidor.';
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Iniciar Sesión';
    }
});