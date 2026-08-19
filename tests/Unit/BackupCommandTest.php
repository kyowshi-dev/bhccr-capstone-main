<?php

namespace Tests\Unit;

use App\Services\BackupService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class BackupCommandTest extends TestCase
{
    private function invokePrivateStatic(string $method, array $conn): array
    {
        $reflection = new ReflectionMethod(BackupService::class, $method);

        return $reflection->invoke(null, $conn);
    }

    private function connection(array $overrides = []): array
    {
        return array_merge([
            'host' => '127.0.0.1',
            'port' => '3306',
            'username' => 'root',
            'password' => 'secret',
            'database' => 'bhcis_db',
        ], $overrides);
    }

    public function test_mysqldump_no_defaults_is_first_option(): void
    {
        $cmd = $this->invokePrivateStatic('mysqldumpCommand', $this->connection());

        $this->assertSame('mysqldump', $cmd[0]);
        $this->assertSame('--no-defaults', $cmd[1], '--no-defaults must be the first option after the binary, otherwise mysql tools reject it');
    }

    public function test_mysql_import_no_defaults_is_first_option(): void
    {
        $cmd = $this->invokePrivateStatic('mysqlImportCommand', $this->connection());

        $this->assertSame('mysql', $cmd[0]);
        $this->assertSame('--no-defaults', $cmd[1], '--no-defaults must be the first option after the binary, otherwise mysql tools reject it');
    }

    public function test_mysqldump_empty_password_flag_is_appended(): void
    {
        $cmd = $this->invokePrivateStatic('mysqldumpCommand', $this->connection(['password' => '']));

        $this->assertContains('--password=', $cmd);
    }

    public function test_mysql_import_empty_password_flag_is_appended(): void
    {
        $cmd = $this->invokePrivateStatic('mysqlImportCommand', $this->connection(['password' => '']));

        $this->assertContains('--password=', $cmd);
    }
}
