const publicAuthLinks = document.getElementById('auth-enlaces');
const profileSession = document.getElementById('perfil-sesion');
const profileName = document.getElementById('perfil-nombre');
const profileStatus = document.getElementById('estado-perfil');
const logoutButton = document.getElementById('cerrar-sesion');

async function loadAuthenticatedProfile() {
    const token = window.BioVetSessionStore.getToken();
    if (!token) {
        return;
    }

    profileStatus.textContent = '';

    try {
        const response = await window.BioVetApi.getProfile(token);

        if (response.status === 401) {
            window.BioVetSessionStore.clear();
            profileStatus.textContent = 'Tu sesión expiró. Inicia sesión nuevamente.';
            return;
        }

        const result = await response.json();
        if (!response.ok || !result.success || typeof result.usuario?.name !== 'string') {
            throw new Error('No se pudo cargar tu perfil.');
        }

        profileName.textContent = `Hola, ${result.usuario.name}`;
        publicAuthLinks.hidden = true;
        profileSession.hidden = false;
        window.BioVetSessionStore.saveUser(result.usuario);
    } catch {
        profileStatus.textContent = 'No se pudo cargar tu perfil. Intenta recargar la página.';
    }
}

window.addEventListener('message', async (event) => {
    if (
        event.origin !== window.location.origin
        || event.source !== window.opener
        || event.data?.type !== 'biovet:session'
        || typeof event.data.token !== 'string'
        || event.data.token.length === 0
        || typeof event.data.usuario?.name !== 'string'
    ) {
        return;
    }

    window.BioVetSessionStore.saveSession(event.data.token, event.data.usuario);
    await loadAuthenticatedProfile();
});

if (window.opener) {
    window.opener.postMessage({ type: 'biovet:session-request' }, window.location.origin);
}

logoutButton.addEventListener('click', () => {
    window.BioVetSessionStore.clear();
    window.location.assign('/login');
});

loadAuthenticatedProfile();
