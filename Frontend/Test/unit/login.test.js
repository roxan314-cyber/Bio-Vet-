'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { test } = require('node:test');

const loginScript = fs.readFileSync(
    path.resolve(__dirname, '..', '..', 'Login', 'login.js'),
    'utf8'
);
const apiClientScript = fs.readFileSync(
    path.resolve(__dirname, '..', '..', '..', 'Backend', 'Frontend-Backend', 'api-client.js'),
    'utf8'
);
const sessionStoreScript = fs.readFileSync(
    path.resolve(__dirname, '..', '..', 'Models', 'session-store.js'),
    'utf8'
);
const authenticatedPageScript = fs.readFileSync(
    path.resolve(__dirname, '..', '..', 'Login', 'session.js'),
    'utf8'
);

async function runLogin({ fetchImplementation, popupEnabled = false }) {
    const elements = new Map();
    for (const id of ['loginForm', 'submitBtn', 'errorMessage']) {
        elements.set(id, {
            disabled: false,
            textContent: id === 'submitBtn' ? 'Iniciar Sesión' : '',
            listeners: {},
            addEventListener(event, listener) {
                this.listeners[event] = listener;
            }
        });
    }

    const values = new Map([
        ['email', 'ana@example.test'],
        ['password', 'correct-password']
    ]);
    const stored = new Map();
    const navigations = [];
    const requests = [];
    const messageListeners = new Map();
    const popup = {
        closed: false,
        messages: [],
        close() {
            this.closed = true;
        },
        postMessage(message, origin) {
            this.messages.push({ message, origin });
        }
    };
    const window = {
        location: {
            origin: 'http://localhost',
            assign: (url) => navigations.push(url)
        },
        open: (...args) => {
            window.openArguments = args;
            return popupEnabled ? popup : null;
        },
        addEventListener: (event, listener) => messageListeners.set(event, listener),
        sessionStorage: {
            setItem: (key, value) => stored.set(key, value),
            getItem: (key) => stored.get(key) ?? null,
            removeItem: (key) => stored.delete(key)
        }
    };

    class MockFormData {
        constructor(form) {
            assert.equal(form, elements.get('loginForm'));
        }

        get(name) {
            return values.get(name) ?? null;
        }
    }

    const context = {
        document: { getElementById: (id) => elements.get(id) },
        FormData: MockFormData,
        fetch: async (...args) => {
            requests.push(args);
            return fetchImplementation(...args);
        },
        sessionStorage: {
            setItem: (key, value) => stored.set(key, value),
            getItem: (key) => stored.get(key) ?? null
        },
        window,
        JSON,
        Error
    };
    vm.runInNewContext(apiClientScript, context);
    vm.runInNewContext(sessionStoreScript, context);
    vm.runInNewContext(loginScript, context);

    const event = { prevented: false, preventDefault() { this.prevented = true; } };
    const submit = elements.get('loginForm').listeners.submit;
    assert.equal(typeof submit, 'function', 'login handler should be registered');
    await submit(event);

    return {
        elements,
        event,
        stored,
        navigations,
        requests,
        popup,
        window,
        messageListeners
    };
}

test('sends email and password, stores the valid session, and redirects home', async () => {
    const result = await runLogin({
        fetchImplementation: async () => ({
            ok: true,
            json: async () => ({
                success: true,
                token: 'valid-token',
                usuario: { id: 7, name: 'Ana Vet', email: 'ana@example.test' }
            })
        })
    });

    assert.equal(result.event.prevented, true);
    assert.equal(result.requests.length, 1);
    assert.equal(result.requests[0][0], '/api/login');
    assert.equal(result.requests[0][1].method, 'POST');
    assert.equal(result.requests[0][1].headers['Content-Type'], 'application/json');
    assert.deepEqual(JSON.parse(result.requests[0][1].body), {
        email: 'ana@example.test',
        password: 'correct-password'
    });
    assert.equal(result.stored.get('biovet.token'), 'valid-token');
    assert.deepEqual(JSON.parse(result.stored.get('biovet.usuario')), {
        id: 7,
        name: 'Ana Vet',
        email: 'ana@example.test'
    });
    assert.deepEqual(result.navigations, ['/']);
    assert.equal(result.elements.get('errorMessage').textContent, '');
    assert.equal(result.elements.get('submitBtn').disabled, false);
    assert.equal(result.elements.get('submitBtn').textContent, 'Iniciar Sesión');
});

test('opens a separate tab and sends it the authenticated session when it is ready', async () => {
    const result = await runLogin({
        popupEnabled: true,
        fetchImplementation: async () => ({
            ok: true,
            json: async () => ({
                success: true,
                token: 'valid-token',
                usuario: { id: 7, name: 'Ana Vet', email: 'ana@example.test' }
            })
        })
    });

    assert.deepEqual(result.window.openArguments, ['/', '_blank']);
    assert.deepEqual(result.navigations, []);
    assert.equal(result.stored.has('biovet.token'), false);
    assert.match(result.elements.get('errorMessage').textContent, /nueva pestaña/);

    result.messageListeners.get('message')({
        source: result.popup,
        origin: 'http://localhost',
        data: { type: 'biovet:session-request' }
    });

    assert.deepEqual(JSON.parse(JSON.stringify(result.popup.messages)), [{
        message: {
            type: 'biovet:session',
            token: 'valid-token',
            usuario: { id: 7, name: 'Ana Vet', email: 'ana@example.test' }
        },
        origin: 'http://localhost'
    }]);
});

test('authenticated home accepts the session only from its same-origin opener', async () => {
    const stored = new Map();
    const listeners = new Map();
    const openerMessages = [];
    const opener = {
        postMessage: (message, origin) => openerMessages.push({ message, origin })
    };
    const elements = new Map([
        ['auth-enlaces', { hidden: false }],
        ['perfil-sesion', { hidden: true }],
        ['perfil-nombre', { textContent: '' }],
        ['estado-perfil', { textContent: '' }],
        ['cerrar-sesion', { addEventListener() {} }]
    ]);
    const window = {
        opener,
        location: { origin: 'http://localhost', assign() {} },
        addEventListener: (event, listener) => listeners.set(event, listener),
        BioVetSessionStore: {
            getToken: () => stored.get('biovet.token') ?? null,
            saveSession: (token, user) => {
                stored.set('biovet.token', token);
                stored.set('biovet.usuario', JSON.stringify(user));
            },
            saveUser: (user) => stored.set('biovet.usuario', JSON.stringify(user)),
            clear: () => stored.clear()
        },
        BioVetApi: {
            getProfile: async (token) => {
                assert.equal(token, 'valid-token');
                return {
                    status: 200,
                    ok: true,
                    json: async () => ({
                        success: true,
                        usuario: { id: 7, name: 'Ana Vet' }
                    })
                };
            }
        }
    };

    vm.runInNewContext(authenticatedPageScript, {
        window,
        document: { getElementById: (id) => elements.get(id) }
    });

    assert.deepEqual(JSON.parse(JSON.stringify(openerMessages)), [{
        message: { type: 'biovet:session-request' },
        origin: 'http://localhost'
    }]);

    await listeners.get('message')({
        source: opener,
        origin: 'https://untrusted.example',
        data: {
            type: 'biovet:session',
            token: 'attacker-token',
            usuario: { name: 'Attacker' }
        }
    });
    assert.equal(stored.has('biovet.token'), false);

    await listeners.get('message')({
        source: opener,
        origin: 'http://localhost',
        data: {
            type: 'biovet:session',
            token: 'valid-token',
            usuario: { id: 7, name: 'Ana Vet' }
        }
    });

    assert.equal(stored.get('biovet.token'), 'valid-token');
    assert.equal(elements.get('auth-enlaces').hidden, true);
    assert.equal(elements.get('perfil-sesion').hidden, false);
    assert.equal(elements.get('perfil-nombre').textContent, 'Hola, Ana Vet');
});

test('closes the unused tab when login is rejected', async () => {
    const result = await runLogin({
        popupEnabled: true,
        fetchImplementation: async () => ({
            ok: false,
            json: async () => ({ mensaje: 'Credenciales inválidas' })
        })
    });

    assert.equal(result.popup.closed, true);
    assert.equal(result.elements.get('errorMessage').textContent, 'Credenciales inválidas');
    assert.deepEqual(result.navigations, []);
});

test('shows the API error without saving a session when credentials are rejected', async () => {
    const result = await runLogin({
        fetchImplementation: async () => ({
            ok: false,
            json: async () => ({ mensaje: 'Credenciales inválidas' })
        })
    });

    assert.equal(result.elements.get('errorMessage').textContent, 'Credenciales inválidas');
    assert.equal(result.stored.has('biovet.token'), false);
    assert.deepEqual(result.navigations, []);
    assert.equal(result.elements.get('submitBtn').disabled, false);
    assert.equal(result.elements.get('submitBtn').textContent, 'Iniciar Sesión');
});

test('rejects a successful response that does not contain a usable session', async () => {
    const result = await runLogin({
        fetchImplementation: async () => ({
            ok: true,
            json: async () => ({ success: true, usuario: { name: 'Ana Vet' } })
        })
    });

    assert.equal(
        result.elements.get('errorMessage').textContent,
        'El servidor no devolvió una sesión válida.'
    );
    assert.equal(result.stored.has('biovet.token'), false);
    assert.deepEqual(result.navigations, []);
});

test('reports network failures and always restores the submit button', async () => {
    const result = await runLogin({
        fetchImplementation: async () => {
            throw new Error('Network unavailable');
        }
    });

    assert.equal(result.elements.get('errorMessage').textContent, 'Network unavailable');
    assert.equal(result.stored.has('biovet.token'), false);
    assert.deepEqual(result.navigations, []);
    assert.equal(result.elements.get('submitBtn').disabled, false);
    assert.equal(result.elements.get('submitBtn').textContent, 'Iniciar Sesión');
});

test('session model retains the legacy sessionStorage keys and clears both values', () => {
    const stored = new Map();
    const context = {
        window: {
            sessionStorage: {
                getItem: (key) => stored.get(key) ?? null,
                setItem: (key, value) => stored.set(key, value),
                removeItem: (key) => stored.delete(key)
            }
        },
        JSON
    };
    vm.runInNewContext(sessionStoreScript, context);
    const sessionStore = context.window.BioVetSessionStore;
    const user = { id: 7, name: 'Ana Vet' };

    sessionStore.saveSession('test-token', user);
    assert.equal(sessionStore.getToken(), 'test-token');
    assert.deepEqual(JSON.parse(stored.get('biovet.usuario')), user);

    sessionStore.saveUser({ id: 7, name: 'Ana Vet Updated' });
    assert.deepEqual(JSON.parse(stored.get('biovet.usuario')), {
        id: 7,
        name: 'Ana Vet Updated'
    });

    sessionStore.clear();
    assert.equal(stored.has('biovet.token'), false);
    assert.equal(stored.has('biovet.usuario'), false);
});
