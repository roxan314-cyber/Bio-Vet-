describe('Registro de BioVet', () => {
    const password = 'Cypress-Register-123!';
    let testEmail;

    beforeEach(() => {
        testEmail = null;
        cy.visit('/registro');
    });

    afterEach(() => {
        if (testEmail) {
            cy.task('deleteLoginTestUser', { email: testEmail }, { log: false });
        }
    });

    it('crea una cuenta y muestra la confirmación sin iniciar sesión automáticamente', () => {
        testEmail = `cypress-${Date.now()}-${Math.random().toString(36).slice(2, 10)}@example.test`;

        cy.intercept('POST', '/api/register').as('registration');
        cy.get('#name').type('Usuario de registro');
        cy.get('#email').type(testEmail);
        cy.get('#password').type(password, { log: false });
        cy.get('#password_confirmation').type(password, { log: false });
        cy.get('#submitBtn').click();

        cy.wait('@registration').its('response.statusCode').should('eq', 201);
        cy.get('#error-msg')
            .should('have.attr', 'data-state', 'success')
            .and('contain.text', 'Cuenta creada. Ya puedes iniciar sesión.');
        cy.get('#name').should('have.value', '');
        cy.get('#email').should('have.value', '');
        cy.get('#password').should('have.value', '');
        cy.get('#password_confirmation').should('have.value', '');
        cy.get('#submitBtn').should('be.enabled').and('have.text', 'Registrarse');
        cy.window()
            .its('sessionStorage')
            .invoke('getItem', 'biovet.token')
            .should('be.null');
        cy.get('a[href="/login"]').should('be.visible');
    });

    it('muestra el conflicto cuando el correo ya está registrado', () => {
        testEmail = `cypress-${Date.now()}-${Math.random().toString(36).slice(2, 10)}@example.test`;

        cy.request('POST', '/api/register', {
            name: 'Cuenta temporal',
            email: testEmail,
            password,
            password_confirmation: password
        }).its('status').should('eq', 201);

        cy.get('#name').type('Otro nombre');
        cy.get('#email').type(testEmail);
        cy.get('#password').type(password, { log: false });
        cy.get('#password_confirmation').type(password, { log: false });
        cy.intercept('POST', '/api/register').as('duplicateRegistration');
        cy.get('#submitBtn').click();

        cy.wait('@duplicateRegistration').its('response.statusCode').should('eq', 409);
        cy.get('#error-msg')
            .should('have.attr', 'data-state', 'error')
            .and('contain.text', 'El email ya está registrado');
        cy.get('#email').should('have.value', testEmail);
        cy.get('#submitBtn').should('be.enabled');
    });
});
