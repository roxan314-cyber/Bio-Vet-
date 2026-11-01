<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HistoriasClinicasTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR . 'Backend'
            . DIRECTORY_SEPARATOR . 'Configuracion'
            . DIRECTORY_SEPARATOR . 'config database.php';

        $database = getAppSetting('BIOVET_TEST_DB', 'BIOVET_TEST_DB');
        if ($database === null || !preg_match('/^biovet_test_[A-Za-z0-9_]*$/', $database)) {
            self::fail(
                'Define BIOVET_TEST_DB con el nombre de una base de pruebas que empiece por biovet_test_.'
            );
        }

        $host = trim((string) getAppSetting('DB_HOST', 'database.default.hostname', '127.0.0.1'));
        $port = trim((string) getAppSetting('DB_PORT', 'DB_PORT', '3306'));
        $username = trim((string) getAppSetting('DB_USER', 'database.default.username', 'root'));
        $password = getAppSetting('DB_PASS', 'database.default.password', '');

        if ($host === '' || strpbrk($host, ";\r\n") !== false) {
            self::fail('DB_HOST no es válido.');
        }
        if (!ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
            self::fail('DB_PORT debe estar entre 1 y 65535.');
        }
        if ($username === '') {
            self::fail('DB_USER no puede estar vacío.');
        }

        $this->db = new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );

        $this->assertSchemaIsInstalled();
        $this->db->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->db) && $this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    public function testTableExistsAndHasExpectedColumns(): void
    {
        $columns = $this->db->query('SHOW COLUMNS FROM `Historias_Clinicas`')
            ->fetchAll(PDO::FETCH_COLUMN);

        self::assertSame([
            'id_consulta',
            'fecha',
            'id_cliente',
            'id_paciente',
            'id_doctor',
            'motivo_consulta',
            'condicion_corporal',
            'diagnostico_dx',
            'plan_accion'
        ], $columns);
    }

    public function testCanInsertAndRetrieveRecord(): void
    {
        $this->insertParentRecords();

        $insert = $this->db->prepare(
            'INSERT INTO `Historias_Clinicas`
                (`fecha`, `id_cliente`, `id_paciente`, `id_doctor`, `motivo_consulta`,
                 `condicion_corporal`, `diagnostico_dx`, `plan_accion`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        self::assertTrue($insert->execute([
            '2026-06-20',
            'CLI-001',
            'PAC-001',
            'DOC-001',
            'El perro no quiere comer desde hace 2 días',
            2,
            'Gastroenteritis presuntiva',
            'Administrar suero y dieta blanda'
        ]));

        $lastId = (int) $this->db->lastInsertId();
        self::assertGreaterThan(0, $lastId);

        $select = $this->db->prepare(
            'SELECT `fecha`, `id_paciente`, `condicion_corporal`
             FROM `Historias_Clinicas`
             WHERE `id_consulta` = ?'
        );
        $select->execute([$lastId]);
        $record = $select->fetch();

        self::assertIsArray($record);
        self::assertSame('2026-06-20', $record['fecha']);
        self::assertSame('PAC-001', $record['id_paciente']);
        self::assertSame(2, (int) $record['condicion_corporal']);
    }

    public function testForeignKeysRejectUnknownPatient(): void
    {
        $this->insertParentRecords();
        $insert = $this->db->prepare(
            'INSERT INTO `Historias_Clinicas`
                (`fecha`, `id_cliente`, `id_paciente`, `id_doctor`)
             VALUES (?, ?, ?, ?)'
        );

        $this->expectException(PDOException::class);
        $insert->execute(['2026-06-20', 'CLI-001', 'PAC-999', 'DOC-001']);
    }

    private function insertParentRecords(): void
    {
        $this->db->exec(
            "INSERT INTO `Clientes` (`id_cliente`, `nombre_cliente`, `cedula_identidad`)
             VALUES ('CLI-001', 'Cliente de prueba', 'TEST-CLI-001')"
        );
        $this->db->exec(
            "INSERT INTO `Pacientes` (`id_paciente`, `nombre_paciente`, `id_cliente`)
             VALUES ('PAC-001', 'Paciente de prueba', 'CLI-001')"
        );
        $this->db->exec(
            "INSERT INTO `Doctores` (`id_doctor`, `nombre_doctor`)
             VALUES ('DOC-001', 'Doctor de prueba')"
        );
    }

    private function assertSchemaIsInstalled(): void
    {
        $requiredColumns = [
            'Clientes' => [
                'id_cliente',
                'nombre_cliente',
                'cedula_identidad',
                'telefono',
                'domicilio'
            ],
            'Pacientes' => [
                'id_paciente',
                'nombre_paciente',
                'raza',
                'sexo',
                'edad',
                'peso_kg',
                'id_cliente'
            ],
            'Doctores' => [
                'id_doctor',
                'nombre_doctor',
                'especialidad',
                'numero_colegiado',
                'telefono',
                'correo',
                'turno',
                'estado'
            ],
            'Historias_Clinicas' => [
                'id_consulta',
                'fecha',
                'id_cliente',
                'id_paciente',
                'id_doctor',
                'motivo_consulta',
                'condicion_corporal',
                'diagnostico_dx',
                'plan_accion'
            ]
        ];

        foreach ($requiredColumns as $table => $expectedColumns) {
            $statement = $this->db->prepare(
                'SELECT COLUMN_NAME
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                 ORDER BY ORDINAL_POSITION'
            );
            $statement->execute([$table]);
            $actualColumns = $statement->fetchAll(PDO::FETCH_COLUMN);
            self::assertSame(
                $expectedColumns,
                $actualColumns,
                "La tabla $table no coincide con el esquema de pruebas."
            );
        }
    }
}
