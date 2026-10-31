# Integración frontend-backend

Esta carpeta contiene el cliente JavaScript compartido que conecta las páginas de BioVet con las rutas PHP públicas:

- `api-client.js` expone `window.BioVetApi.login()`, `.register()` y `.getProfile()`.
- Las rutas `/api/login`, `/api/register` y `/api/users/me` se sirven desde `Proyecto/router.php` o se reenvían al backend PHP cuando se usa Express.
- El cliente devuelve las respuestas `fetch` sin convertirlas para que cada formulario maneje el contrato y los mensajes adecuados.

Las páginas cargan este archivo antes de sus scripts de login, registro o sesión. Si se agrega una ruta estática en el cliente, debe publicarse también en ambos servidores. El cierre de sesión actual elimina el JWT de `sessionStorage`; no existe sesión de servidor ni revocación de tokens.
