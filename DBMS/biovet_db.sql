CREATE DATABASE IF NOT EXISTS biovet_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE biovet_db;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(30) NOT NULL DEFAULT 'veterinario',
  `telefono` varchar(30) DEFAULT NULL,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clientes` (
  `id_cliente` varchar(10) NOT NULL,
  `nombre_cliente` varchar(100) NOT NULL,
  `cedula_identidad` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `domicilio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `doctores` (
  `id_doctor` varchar(10) NOT NULL,
  `nombre_doctor` varchar(100) NOT NULL,
  `especialidad` varchar(50) DEFAULT 'Medicina Veterinaria',
  `numero_colegiado` varchar(30) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `turno` varchar(20) DEFAULT NULL,
  `estado` varchar(15) DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `historias_clinicas` (
  `id_consulta` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `id_cliente` varchar(10) DEFAULT NULL,
  `id_paciente` varchar(10) DEFAULT NULL,
  `id_doctor` varchar(10) DEFAULT NULL,
  `motivo_consulta` text DEFAULT NULL,
  `condicion_corporal` int(11) DEFAULT NULL,
  `diagnostico_dx` text DEFAULT NULL,
  `plan_accion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pacientes` (
  `id_paciente` varchar(10) NOT NULL,
  `nombre_paciente` varchar(50) NOT NULL,
  `raza` varchar(50) DEFAULT NULL,
  `sexo` varchar(10) DEFAULT NULL,
  `edad` varchar(30) DEFAULT NULL,
  `peso_kg` decimal(5,2) DEFAULT NULL,
  `id_cliente` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `cedula_identidad` (`cedula_identidad`);

ALTER TABLE `doctores`
  ADD PRIMARY KEY (`id_doctor`);

ALTER TABLE `historias_clinicas`
  ADD PRIMARY KEY (`id_consulta`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_paciente` (`id_paciente`),
  ADD KEY `id_doctor` (`id_doctor`);

ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id_paciente`),
  ADD KEY `id_cliente` (`id_cliente`);

ALTER TABLE `historias_clinicas`
  MODIFY `id_consulta` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `historias_clinicas`
  ADD CONSTRAINT `historias_clinicas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`),
  ADD CONSTRAINT `historias_clinicas_ibfk_2` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id_paciente`),
  ADD CONSTRAINT `historias_clinicas_ibfk_3` FOREIGN KEY (`id_doctor`) REFERENCES `doctores` (`id_doctor`);

ALTER TABLE `pacientes`
  ADD CONSTRAINT `pacientes_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;