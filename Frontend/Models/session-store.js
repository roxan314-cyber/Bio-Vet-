'use strict';

(() => {
    const tokenKey = 'biovet.token';
    const userKey = 'biovet.usuario';

    window.BioVetSessionStore = Object.freeze({
        getToken() {
            return window.sessionStorage.getItem(tokenKey);
        },

        saveSession(token, user) {
            window.sessionStorage.setItem(tokenKey, token);
            window.sessionStorage.setItem(userKey, JSON.stringify(user));
        },

        saveUser(user) {
            window.sessionStorage.setItem(userKey, JSON.stringify(user));
        },

        clear() {
            window.sessionStorage.removeItem(tokenKey);
            window.sessionStorage.removeItem(userKey);
        }
    });
})();
