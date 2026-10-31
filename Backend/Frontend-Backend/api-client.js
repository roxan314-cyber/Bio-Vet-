'use strict';

(() => {
    const postJson = (path, payload) => fetch(path, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        cache: 'no-store'
    });

    window.BioVetApi = Object.freeze({
        login(credentials) {
            return postJson('/api/login', credentials);
        },
        register(registration) {
            return postJson('/api/register', registration);
        },
        getProfile(token) {
            return fetch('/api/users/me', {
                headers: { Authorization: `Bearer ${token}` },
                cache: 'no-store'
            });
        }
    });
})();
