describe('Página de inicio de BioVet', () => {
    beforeEach(() => {
        cy.visit('/', {
            onBeforeLoad(window) {
                window.sessionStorage.clear();
            }
        });
    });

    it('muestra las secciones informativas y las tarjetas de servicios', () => {
        cy.title().should('contain', 'BioVet Enterprise');
        cy.get('.textos-header h1').should('have.text', 'BioVet Enterprise');
        cy.get('#equipo').within(() => {
            cy.get('h3').should('contain.text', '¿Quiénes somos?');
            cy.get('.card').should('have.length', 3);
            cy.get('.card h4').then(($headings) => {
                expect([...$headings].map((heading) => heading.textContent.trim()))
                    .to.deep.equal(['Misión', 'Visión', 'Valores']);
            });
        });

        cy.get('#servicio').within(() => {
            cy.get('.servicio-card').should('have.length', 6);
            cy.get('.servicio-card h4').then(($headings) => {
                expect([...$headings].map((heading) => heading.textContent.trim()))
                    .to.deep.equal([
                        'Consultas veterinarias',
                        'Vacunación y desparasitación',
                        'Cirugías médicas',
                        'Diagnóstico por imagen',
                        'Laboratorio clínico',
                        'Emergencias 24/7'
                    ]);
            });
            cy.get('a').should('not.exist');
        });
        cy.get('#contacto')
            .should('be.visible')
            .within(() => {
                cy.get('form').should('not.exist');
                cy.get('a[href="/login"]').should('be.visible');
                cy.get('a[href="/registro"]').should('be.visible');
            });
        cy.get('nav[aria-label="Navegación principal"]').should('exist');
        cy.get('.skip-link').should('have.attr', 'href', '#contenido');
        cy.get('a[href^="#"]').each(($link) => {
            cy.get($link.attr('href')).should('exist');
        });
    });

    it('mantiene visibles las opciones públicas para visitantes', () => {
        cy.get('#auth-enlaces')
            .should('be.visible')
            .within(() => {
                cy.get('a[href="/login"]').should('contain.text', 'Iniciar sesión');
                cy.get('a[href="/registro"]').should('contain.text', 'Registrarse');
            });

        cy.get('#perfil-sesion').should('not.be.visible');
    });

    it('redirige la ruta heredada al único formulario oficial', () => {
        cy.visit('/indexlogin.html');
        cy.location('pathname').should('eq', '/login');
        cy.get('#loginForm').should('be.visible');
        cy.get('#email').should('have.attr', 'type', 'email');
    });
});