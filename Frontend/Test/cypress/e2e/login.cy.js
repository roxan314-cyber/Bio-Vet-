describe('Inicio de sesión - integración con BioVet', () => {
    const password = 'Cypress-Test-123!';
    let testEmail;

    beforeEach(() => {
        testEmail = null;
        cy.visit('/login', {
            onBeforeLoad(window) {
                window.sessionStorage.clear();
            }
        });
    });

    afterEach(() => {
        if (testEmail) {
            cy.task('deleteLoginTestUser', { email: testEmail }, { log: false });
        }
    });

    it('usa el tema BioVet y mantiene el formulario utilizable en móvil', () => {
        cy.viewport(375, 812);
        cy.get('.login-box').should('be.visible').and(($box) => {
            expect(getComputedStyle($box[0]).backgroundColor).to.equal('rgba(255, 255, 255, 0.96)');
        });
        cy.get('#submitBtn').should('have.css', 'background-color', 'rgb(44, 122, 123)');
        cy.get('#email').type('correo@example.test');
        cy.get('#password').focus();
        cy.get('#password').should('be.focused');
        cy.get('body').should(($body) => {
            expect($body[0].scrollWidth).to.be.at.most($body[0].clientWidth);
        });
    });

    it('inicia sesión, muestra el perfil y permite cerrar sesión', () => {
        testEmail = `cypress-${Date.now()}-${Math.random().toString(36).slice(2, 10)}@example.test`;

        cy.window().then((window) => {
            cy.stub(window, 'open').returns(null);
        });

        cy.request('POST', '/api/register', {
            name: 'Usuario de prueba',
            email: testEmail,
            password,
            password_confirmation: password
        }).its('status').should('eq', 201);

        cy.intercept('GET', '/api/users/me').as('profile');
        cy.get('#email').type(testEmail);
        cy.get('#password').type(password, { log: false });
        cy.get('#submitBtn').click();

        cy.location('pathname').should('eq', '/');
        cy.window()
            .its('sessionStorage')
            .invoke('getItem', 'biovet.token')
            .should('be.a', 'string')
            .and('not.be.empty');
        cy.wait('@profile').its('response.statusCode').should('eq', 200);
        cy.get('#perfil-nombre')
            .should('be.visible')
            .and('contain.text', 'Hola, Usuario de prueba');
        cy.get('.enlaces a[href="#inicio"]').should('be.visible');
        cy.get('.enlaces a[href="#equipo"]').should('be.visible');
        cy.get('.enlaces a[href="#servicio"]').should('be.visible');
        cy.contains('h3', '¿Quiénes somos?').should('be.visible');
        cy.get('#servicio').should('be.visible')
            .and('contain.text', 'Nuestros Servicios')
            .and('contain.text', 'Consultas veterinarias');

        cy.get('#cerrar-sesion').click();
        cy.get('#loginForm').should('be.visible');
        cy.location('pathname').should('eq', '/login');
        cy.window()
            .its('sessionStorage')
            .invoke('getItem', 'biovet.token')
            .should('be.null');
    });

    it('rechaza credenciales inválidas y muestra el error del servidor', () => {
        cy.get('#email').type('no-existe@example.test');
        cy.get('#password').type('incorrecta', { log: false });
        cy.get('#submitBtn').click();

        cy.get('#errorMessage')
            .should('be.visible')
            .and('contain.text', 'Credenciales inválidas');
        cy.window()
            .its('sessionStorage')
            .invoke('getItem', 'biovet.token')
            .should('be.null');
    });
});