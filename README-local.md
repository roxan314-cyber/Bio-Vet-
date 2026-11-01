# Ejecutar BioVet localmente en Windows

El servidor local usa el frontend estático y los endpoints PHP existentes, servido por el servidor integrado de PHP. MySQL se ejecuta localmente por separado mediante XAMPP.

1. En el panel de XAMPP, inicia **MySQL**.
2. Abre phpMyAdmin y prepara la base:
   - Instalación nueva: ejecuta `DBMS/biovet_db.sql` para crear el esquema clínico y la tabla `users`.
   - Base clínica ya existente: ejecuta solo `DBMS/users.sql`; crea la tabla de usuarios sin borrar las tablas existentes.
3. Revisa las claves de conexión en `.env`. Para este servidor se usan `database.default.hostname`, `database.default.database`, `database.default.username` y `database.default.password`. El puerto predeterminado es `3306`; si tu MySQL usa otro, define `DB_PORT` en la sesión de PowerShell.
4. Desde la raíz del proyecto, ejecuta el diagnóstico de solo lectura para comprobar la conexión y el esquema de usuarios:

   ```powershell
   php ".\Protec bases de datos.php"
   ```

   El comando devuelve un código distinto de cero si la base de datos no está disponible, falta una columna requerida o la tabla `users` no tiene un índice único para `email`.
5. Desde PowerShell, ejecuta:

   ```powershell
   Set-Location "C:\Users\roger\Desktop\Bio-Vet-Enterprise\Proyecto"
   .\run-server.ps1
   ```

   El primer arranque agrega a `.env` un `JWT_SECRET` aleatorio si todavía no existe. No publiques ese archivo ni lo subas al control de versiones.

6. Abre `http://127.0.0.1:8000/`. Para probar el backend y la conexión a MySQL, abre `http://127.0.0.1:8000/api/health`; una respuesta correcta contiene `"status":"ok"` y `"database":"connected"`.

Rutas incluidas: `/login`, `/registro`, `/api/login`, `/api/register`, `/api/verify-token`, `/api/users/me` (requiere token Bearer) y `/api/health`. El formulario oficial está en `Frontend/Index/Index Login/indexlogin.html`; la URL heredada `/indexlogin.html` redirige a `/login`. Al iniciar sesión se abre una pestaña independiente con la portada completa (Inicio, Quiénes somos y Servicios) y el perfil autenticado; la pestaña del login permanece abierta. Si el navegador bloquea la ventana emergente, el inicio continúa en la pestaña actual. El botón «Cerrar sesión» elimina el token de la pestaña autenticada y vuelve al login. El servidor escucha solo en `127.0.0.1`, para desarrollo local.

El frontend mantiene las vistas HTML en `Frontend/`, los controladores de página en `Frontend/Login/` y `Frontend/Registro/`, el adaptador de sesión en `Frontend/Models/session-store.js` y el servicio HTTP compartido en `Backend/Frontend-Backend/api-client.js`. `api-client.js` y `session-store.js` se sirven como `/assets/api-client.js` y `/assets/session-store.js` antes de los controladores que los usan; el adaptador conserva las claves `biovet.token` y `biovet.usuario` en `sessionStorage`. La sesión se transfiere a la pestaña autenticada mediante mensajes `postMessage` validados por origen y ventana, y allí se guarda en su propio `sessionStorage`. El cierre de sesión se realiza en el navegador y el perfil se consulta con el token Bearer. El flujo mantenido usa las rutas de `router.php` y también está disponible a través de la capa estática/proxy de Express.

Los archivos Java de reportes, incluido el punto de entrada `Backend/Reportes/Main.java`, están agrupados en `Backend/Reportes/`. El ejemplo consulta las historias clínicas existentes y relaciona cliente, paciente y doctor. `GestorReportes` acepta filtros `fechaDesde`/`fechaHasta` (`LocalDate`), `idCliente`, `idPaciente`, `idDoctor` y `condicionCorporalMinima` (1–5); la paginación admite hasta 500 filas por página. No crea ni modifica datos.

`Backend/Clases/` contiene los modelos Java `Cliente`, `Doctor`, `Paciente` e `HistoriaClinica`, alineados con las tablas homónimas del esquema clínico. Los campos obligatorios, longitudes y restricciones de peso/condición corporal se validan al crear las instancias; el ID de una consulta puede ser nulo antes de insertarla.

El helper `Backend/Configuracion/Bases de datos/Protección Tablas.php` es opcional y se conecta al `MySQLConnectionManager`; el archivo raíz `Protección Tablas.php` se conserva como punto de entrada de compatibilidad hacia ese helper. No está insertado en las rutas de autenticación actuales, que usan PDO y consultas preparadas. Para cifrar campos desde ese helper, define `DATA_ENCRYPTION_KEY` como 32 bytes aleatorios o como su representación Base64 y conserva esa clave para poder descifrar datos existentes. El helper bloquea escrituras a tablas sensibles, incluida `users`; no uses esa restricción en un flujo que necesite modificar esas tablas.

El backend PHP mantiene `router.php` como único front controller y organiza la autenticación en controladores, servicios, repositorios PDO y el modelo `User`, con composición ligera sin framework ni Composer. `Backend/routes/web-routes.json` es la fuente compartida de rutas, páginas y assets para PHP y Express; las políticas HTTP que difieren entre superficies se declaran por separado. Los límites y comandos de prueba del backend están en [`Backend/README-MVC.md`](Backend/README-MVC.md); `php Backend/tests/run.php` ejecuta pruebas aisladas sin conectarse a MySQL.

## Apache

El archivo `Proyecto/.htaccess` aplica reglas para Apache 2.4 cuando `Proyecto` es el `DocumentRoot`: desactiva el listado de directorios, niega acceso directo a scripts PHP distintos de `router.php` y dirige las URL públicas al router de la aplicación. Habilita `mod_rewrite` y `mod_headers`, y permite en la configuración del sitio las directivas de `.htaccess` (`AllowOverride FileInfo AuthConfig Limit Options`). El servidor local documentado arriba usa el servidor integrado de PHP y no requiere este archivo.

## Prueba de integración del inicio de sesión

Con MySQL iniciado, las dependencias instaladas en `Frontend/Login` y el servidor local activo, ejecuta desde PowerShell:

```powershell
Set-Location "C:\Users\roger\Desktop\Bio-Vet-Enterprise\Proyecto\Frontend\Login"
npm test
```

La suite Cypress también valida la portada pública, el registro real (incluidos correo duplicado y confirmación de éxito) y el flujo de login/perfil. Las pruebas que escriben en MySQL crean cuentas temporales con correo `@example.test` único y eliminan exclusivamente esa cuenta al finalizar; no reinician ni vacían la base de datos. Las pruebas unitarias ejercitan el JavaScript de login y registro sin conectarse a MySQL. Si el servidor está en otra dirección, define `BIOVET_TEST_BASE_URL` antes de ejecutar `npm test` (por ejemplo, `http://127.0.0.1:3000` para Express).
