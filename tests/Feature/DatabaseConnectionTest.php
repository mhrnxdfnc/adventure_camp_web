<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PDO;
use PDOException;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    /**
     * Pastikan file .env dikonfigurasi dengan standar MySQL Laragon port 3306 dan database adventure_camp_rental
     */
    public function test_env_database_configuration_matches_laragon_mysql_standards(): void
    {
        $envPath = base_path('.env');
        $this->assertFileExists($envPath);

        $envContent = file_get_contents($envPath);
        $this->assertStringContainsString('DB_CONNECTION=mysql', $envContent);
        $this->assertStringContainsString('DB_HOST=127.0.0.1', $envContent);
        $this->assertStringContainsString('DB_PORT=3306', $envContent);
        $this->assertStringContainsString('DB_DATABASE=adventure_camp_rental', $envContent);
        $this->assertStringContainsString('DB_USERNAME=root', $envContent);
    }

    /**
     * Pastikan koneksi langsung MySQL ke Laragon port 3306 berhasil jika service aktif
     */
    public function test_live_mysql_connection_to_laragon_port_3306(): void
    {
        try {
            $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=adventure_camp_rental', 'root', 'root', [
                PDO::ATTR_TIMEOUT => 2,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $statement = $pdo->query('SELECT 1 as is_connected');
            $row = $statement->fetch(PDO::FETCH_ASSOC);

            $this->assertIsArray($row);
            $this->assertEquals(1, $row['is_connected']);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Service MySQL Laragon port 3306 belum aktif atau password berbeda: '.$e->getMessage());
        }
    }

    /**
     * Pastikan koneksi database aktif saat ini dapat melakukan query dasar
     */
    public function test_active_database_connection_can_execute_query(): void
    {
        $result = DB::select('SELECT 1 as test_col');

        $this->assertNotEmpty($result);
        $this->assertEquals(1, $result[0]->test_col);
    }

    /**
     * Pastikan QueryException koneksi database (2002 / connection refused) ditangani secara aman dengan tampilan ramah ERR_DB_CONN tanpa bocor kredensial
     */
    public function test_query_exception_connection_error_renders_safe_error_page(): void
    {
        Route::get('/test-db-error-view', function () {
            throw new QueryException(
                'mysql',
                'select * from dummy',
                [],
                new \Exception('SQLSTATE[HY000] [2002] Connection refused: password=super_secret_db_password_123', 2002)
            );
        });

        $response = $this->get('/test-db-error-view');

        $response->assertStatus(503);
        $response->assertSee('ERR_DB_CONN');
        $response->assertSee('Layanan Database Tidak Tersedia');
        // Kredensial dan detail internal rahasia tidak boleh bocor ke pengguna
        $response->assertDontSee('super_secret_db_password_123');
        $response->assertDontSee('SQLSTATE');
    }

    /**
     * Pastikan QueryException koneksi database pada API request mengembalikan JSON dengan kode ERR_DB_CONN
     */
    public function test_query_exception_connection_error_returns_json_on_api(): void
    {
        Route::get('/api/test-db-error', function () {
            throw new QueryException(
                'mysql',
                'select * from dummy',
                [],
                new \Exception('SQLSTATE[HY000] [2002] Connection refused: password=super_secret_db_password_123', 2002)
            );
        });

        $response = $this->getJson('/api/test-db-error');

        $response->assertStatus(503);
        $response->assertJson([
            'status' => 'error',
            'code' => 'ERR_DB_CONN',
        ]);
        $response->assertJsonMissing([
            'super_secret_db_password_123',
        ]);
    }

    /**
     * Pastikan PDOException koneksi database (1045 access denied) ditangani secara aman
     */
    public function test_pdo_exception_connection_error_renders_safe_error_page(): void
    {
        Route::get('/test-pdo-error-view', function () {
            throw new PDOException("SQLSTATE[HY000] [1045] Access denied for user 'root'@'127.0.0.1' (using password: YES)", 1045);
        });

        $response = $this->get('/test-pdo-error-view');

        $response->assertStatus(503);
        $response->assertSee('ERR_DB_CONN');
        $response->assertSee('Layanan Database Tidak Tersedia');
    }
}
