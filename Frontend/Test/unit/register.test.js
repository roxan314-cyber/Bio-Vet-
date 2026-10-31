'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { test } = require('node:test');

const registrationScript = fs.readFileSync(
    path.resolve(__dirname, '..', '..', 'Registro', 'register.js'),
    'utf8'
);
const apiClientScript = fs.readFileSync(
    path.resolve(__dirname, '..', '..', '..', 'Backend', 'Frontend-Backend', 'api-client.js'),
    'utf8'
);

async function runRegistration({ fetchImplementation }) {
    const elements = new Map();
    for (const id of ['registroForm', 'error-msg']) {
        elements.set(id, {
            textContent: '',
            attributes: {},
            listeners: {},
            setAttribute(name, value) {
                this.attributes[name] = value;
            },
            addEventListener(event, listener) {
                this.listeners[event] = listener;
            }
        });
    }

    const submitButton = {
        disabled: false,
        textContent: 'Registrarse'
    };
    const form = elements.get('registroForm');
    form.querySelector = (selector) => {
        assert.equal(selector, 'button[type="submit"]');
        return submitButton;
    };
    let resetCount = 0;
    form.reset = () => { resetCount += 1; };

    const values = new Map([
        ['name', 'Ana Vet'],
        ['email', 'ana@example.test'],
        ['password', 'correct-password'],
        ['password_confirmation', 'correct-password']
    ]);
    const requests = [];

    class MockFormData {
        constructor(actualForm) {
            assert.equal(actualForm, form);
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
        Object,
        Array,
        Error,
        JSON,
        window: {}
    };
    vm.runInNewContext(apiClientScript, context);
    vm.runInNewContext(registrationScript, context);

    const event = { prevented: false, preventDefault() { this.prevented = true; } };
    const submit = form.listeners.submit;
    assert.equal(typeof submit, 'function', 'registration handler should be registered');
    await submit(event);

    return {
        elements,
        event,
        submitButton,
        requests,
        resetCount
    };
}

test('sends the registration fields and reports a successful account creation', async () => {
    const result = await runRegistration({
        fetchImplementation: async () => ({
            ok: true,
            status: 201,
            json: async () => ({
                success: true,
                mensaje: 'Usuario registrado exitosamente',
                usuario: { id: 7, name: 'Ana Vet', email: 'ana@example.test' }
            })
        })
    });

    assert.equal(result.event.prevented, true);
    assert.equal(result.requests.length, 1);
    assert.equal(result.requests[0][0], '/api/register');
    assert.equal(result.requests[0][1].method, 'POST');
    assert.equal(result.requests[0][1].headers['Content-Type'], 'application/json');
    assert.deepEqual(JSON.parse(result.requests[0][1].body), {
        name: 'Ana Vet',
        email: 'ana@example.test',
        password: 'correct-password',
        password_confirmation: 'correct-password'
    });
    assert.equal(
        result.elements.get('error-msg').textContent,
        'Cuenta creada. Ya puedes iniciar sesión.'
    );
    assert.equal(result.elements.get('error-msg').attributes['data-state'], 'success');
    assert.equal(result.resetCount, 1);
    assert.equal(result.submitButton.disabled, false);
    assert.equal(result.submitButton.textContent, 'Registrarse');
});

test('displays duplicate email conflicts and keeps the form data', async () => {
    const result = await runRegistration({
        fetchImplementation: async () => ({
            ok: false,
            status: 409,
            json: async () => ({ success: false, mensaje: 'El email ya está registrado' })
        })
    });

    assert.equal(
        result.elements.get('error-msg').textContent,
        'El email ya está registrado'
    );
    assert.equal(result.elements.get('error-msg').attributes['data-state'], 'error');
    assert.equal(result.resetCount, 0);
    assert.equal(result.submitButton.disabled, false);
    assert.equal(result.submitButton.textContent, 'Registrarse');
});

test('formats backend validation errors returned as strings or arrays', async () => {
    const result = await runRegistration({
        fetchImplementation: async () => ({
            ok: false,
            status: 400,
            json: async () => ({
                success: false,
                errors: {
                    name: 'El nombre es requerido',
                    password: ['La contraseña debe tener al menos 8 caracteres']
                }
            })
        })
    });

    assert.equal(
        result.elements.get('error-msg').textContent,
        'El nombre es requerido. La contraseña debe tener al menos 8 caracteres'
    );
    assert.equal(result.resetCount, 0);
});

test('rejects malformed success responses and invalid JSON without resetting the form', async () => {
    const missingUser = await runRegistration({
        fetchImplementation: async () => ({
            ok: true,
            json: async () => ({ success: true })
        })
    });
    assert.equal(
        missingUser.elements.get('error-msg').textContent,
        'El servidor no confirmó la creación de la cuenta.'
    );
    assert.equal(missingUser.resetCount, 0);

    const invalidJson = await runRegistration({
        fetchImplementation: async () => ({
            ok: true,
            json: async () => { throw new SyntaxError('Invalid JSON'); }
        })
    });
    assert.equal(
        invalidJson.elements.get('error-msg').textContent,
        'El servidor devolvió una respuesta no válida.'
    );
    assert.equal(invalidJson.resetCount, 0);
});

test('reports network errors and always restores the submit button', async () => {
    const result = await runRegistration({
        fetchImplementation: async () => { throw new Error('Failed to fetch'); }
    });

    assert.equal(
        result.elements.get('error-msg').textContent,
        'No se pudo conectar con el servidor. Intenta nuevamente.'
    );
    assert.equal(result.elements.get('error-msg').attributes['data-state'], 'error');
    assert.equal(result.resetCount, 0);
    assert.equal(result.submitButton.disabled, false);
    assert.equal(result.submitButton.textContent, 'Registrarse');
});
