'use strict';

const { execFileSync } = require('node:child_process');
const path = require('node:path');

module.exports = {
    allowCypressEnv: false,
    e2e: {
        baseUrl: process.env.BIOVET_TEST_BASE_URL || 'http://127.0.0.1:8000',
        specPattern: 'cypress/e2e/**/*.cy.js',
        supportFile: false,
        setupNodeEvents(on) {
            on('task', {
                deleteLoginTestUser({ email }) {
                    if (
                        typeof email !== 'string'
                        || !/^cypress-[a-z0-9-]+@example\.test$/i.test(email)
                    ) {
                        throw new Error('Refusing to delete a non-test BioVet account.');
                    }

                    const phpConfig = path.resolve(
                        __dirname,
                        '..',
                        '..',
                        'Backend',
                        'Configuracion',
                        'config database.php'
                    );
                    const deleteTestAccount = [
                        'require $argv[1];',
                        "$statement = getDBConnection()->prepare('DELETE FROM users WHERE email = ?');",
                        '$statement->execute([$argv[2]]);'
                    ].join(' ');

                    execFileSync(
                        process.env.PHP_BINARY || 'php',
                        ['-r', deleteTestAccount, phpConfig, email],
                        { stdio: 'pipe' }
                    );
                    return null;
                }
            });
        }
    }
};
