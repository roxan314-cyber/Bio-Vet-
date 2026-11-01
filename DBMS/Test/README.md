# Pruebas de integración MySQL

Estas pruebas solo se conectan a una base cuyo nombre comienza por `biovet_test_`. Nunca borran tablas ni modifican la base de la aplicación. Cada prueba corre en una transacción y revierte sus registros al terminar.

## Preparar dependencias

Requisitos: PHP 8.2+, las extensiones `pdo` y `pdo_mysql`, Composer y MySQL/MariaDB.

Desde `Proyecto/DBMS`:

```powershell
composer install
```

## Preparar la base aislada

En phpMyAdmin o desde el cliente MySQL, importa `Test/schema.sql`. Este script crea solamente la base `biovet_test_db` y sus tablas con `CREATE TABLE IF NOT EXISTS`; no elimina ni reemplaza datos/tablas existentes.

Configura el acceso de prueba en la sesión antes de ejecutar los tests:

```powershell
$env:BIOVET_TEST_DB = "biovet_test_db"
$env:DB_HOST = "127.0.0.1"
$env:DB_PORT = "3306"
$env:DB_USER = "root"
$env:DB_PASS = ""
```

El test también lee las claves `database.default.hostname`, `database.default.database`, `database.default.username` y `database.default.password` del archivo `.env` del proyecto. `BIOVET_TEST_DB` debe establecerse explícitamente y debe comenzar por `biovet_test_`; nunca se usa `DB_NAME` como destino de pruebas.

Ejecuta:

```powershell
composer test
```

Si la base no existe, no se puede conectar o el esquema no está instalado, la suite falla con un error visible; no marca las pruebas como omitidas.
