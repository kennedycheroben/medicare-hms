<?php

namespace Tests\Feature;

use Symfony\Component\Process\Process;
use Tests\TestCase;

class DeploymentReadinessTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = base_path();
    }

    /**
     * Parse key-value pairs from an environment file into an associative array.
     */
    private function parseEnvFile(string $filePath): array
    {
        $this->assertFileExists($filePath, "Environment file must exist: {$filePath}");
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $data = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                $data[$key] = $value;
            }
        }

        return $data;
    }

    // =========================================================================
    // 1. Production Configuration Security Hardening Tests
    // =========================================================================

    public function test_production_environment_template_enforces_security_hardening(): void
    {
        $prodEnvPath = $this->projectRoot.'/.env.production.example';
        $env = $this->parseEnvFile($prodEnvPath);

        // Core security flags
        $this->assertEquals('production', $env['APP_ENV'] ?? null, 'APP_ENV must be production');
        $this->assertEquals('false', strtolower($env['APP_DEBUG'] ?? ''), 'APP_DEBUG must be strictly false in production');
        $this->assertStringStartsWith('https://', $env['APP_URL'] ?? '', 'APP_URL must enforce HTTPS in production');
        $this->assertEquals('12', $env['BCRYPT_ROUNDS'] ?? null, 'BCRYPT_ROUNDS must be 12 for production password hashing');

        // Session & cookie hardening
        $this->assertEquals('database', $env['SESSION_DRIVER'] ?? null, 'SESSION_DRIVER must be database for concurrency');
        $this->assertEquals('true', strtolower($env['SESSION_ENCRYPT'] ?? ''), 'SESSION_ENCRYPT must be true');
        $this->assertEquals('true', strtolower($env['SESSION_SECURE_COOKIE'] ?? ''), 'SESSION_SECURE_COOKIE must be true');
        $this->assertEquals('true', strtolower($env['SESSION_HTTP_ONLY'] ?? ''), 'SESSION_HTTP_ONLY must be true');
        $this->assertEquals('lax', strtolower($env['SESSION_SAME_SITE'] ?? ''), 'SESSION_SAME_SITE must be lax');

        // Logging & rotation
        $this->assertEquals('daily', $env['LOG_CHANNEL'] ?? null, 'LOG_CHANNEL must be daily rotation');
        $this->assertNotEmpty($env['LOG_DAILY_DAYS'] ?? null, 'LOG_DAILY_DAYS must be defined to prevent disk exhaustion');

        // Mail configuration requires SMTP in production
        $this->assertEquals('smtp', $env['MAIL_MAILER'] ?? null, 'MAIL_MAILER must be smtp for real transactional delivery');

        // Hospital operational mode
        $this->assertEquals('false', strtolower($env['HOSPITAL_DEMO_MODE'] ?? ''), 'HOSPITAL_DEMO_MODE must be false in production');
    }

    public function test_env_example_and_production_example_configuration_parity(): void
    {
        $exampleEnv = $this->parseEnvFile($this->projectRoot.'/.env.example');
        $prodEnv = $this->parseEnvFile($this->projectRoot.'/.env.production.example');

        // List of keys required by the application runtime across both environments
        $essentialRuntimeKeys = [
            'APP_NAME',
            'APP_ENV',
            'APP_KEY',
            'APP_DEBUG',
            'APP_URL',
            'APP_LOCALE',
            'APP_FALLBACK_LOCALE',
            'APP_MAINTENANCE_DRIVER',
            'BCRYPT_ROUNDS',
            'DB_CONNECTION',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
            'SESSION_DRIVER',
            'SESSION_LIFETIME',
            'SESSION_ENCRYPT',
            'SESSION_PATH',
            'SESSION_DOMAIN',
            'QUEUE_CONNECTION',
            'CACHE_STORE',
            'MAIL_MAILER',
            'MAIL_FROM_ADDRESS',
            'MAIL_FROM_NAME',
            'HOSPITAL_NAME',
            'HOSPITAL_CITY',
            'HOSPITAL_COUNTRY',
            'HOSPITAL_PHONE',
            'HOSPITAL_EMAIL',
            'HOSPITAL_CURRENCY',
            'HOSPITAL_DEMO_MODE',
        ];

        foreach ($essentialRuntimeKeys as $key) {
            $this->assertArrayHasKey($key, $prodEnv, "Required application configuration key '{$key}' missing from .env.production.example");
            $this->assertArrayHasKey($key, $exampleEnv, "Required application configuration key '{$key}' missing from .env.example");
        }

        // Verify that development-only mock credentials are NOT carried into production
        $this->assertNotEquals('root', $prodEnv['DB_USERNAME'], 'Production template should not use default local root username');
        $this->assertNotEquals('log', $prodEnv['MAIL_MAILER'], 'Production template should not use log mailer');
        $this->assertNotEquals('true', strtolower($prodEnv['APP_DEBUG']), 'Production template must not enable debug mode');
    }

    // =========================================================================
    // 2. Operational, Legal & User Workbook Route Verification Tests
    // =========================================================================

    public function test_operational_and_legal_documentation_routes_render_cleanly(): void
    {
        // 1. User Guide / Workbook
        $guideResponse = $this->get(route('guide'));
        $guideResponse->assertStatus(200);
        $guideResponse->assertSee('MediCare Hospital Management System');
        $guideResponse->assertSee('User Workbook');
        $guideResponse->assertSee('Who is MediCare HMS For?');
        $guideResponse->assertSee('Step-by-Step Patient Workflows');
        $guideResponse->assertSee('Clinical Operations &amp; Staff Procedures', false);
        $guideResponse->assertSee('Pharmacy &amp; FEFO Inventory Management', false);

        // 2. Terms of Service
        $termsResponse = $this->get(route('terms'));
        $termsResponse->assertStatus(200);
        $termsResponse->assertSee('Terms of Service');
        $termsResponse->assertSee('Emergency Care Notice');
        $termsResponse->assertSee('Appointment Booking &amp; Scheduling', false);
        $termsResponse->assertSee('Billing, Insurance &amp; Payment Policies', false);
        $termsResponse->assertSee('Health Information Disclaimer');

        // 3. Privacy Policy (KDPA 2019)
        $privacyResponse = $this->get(route('privacy'));
        $privacyResponse->assertStatus(200);
        $privacyResponse->assertSee('Privacy Policy');
        $privacyResponse->assertSee('Kenya Data Protection Act, 2019');
        $privacyResponse->assertSee('Lawful Basis for Processing');
        $privacyResponse->assertSee('Your Rights as a Data Subject');
        $privacyResponse->assertSee('Retention &amp; Disclosure', false);
    }

    // =========================================================================
    // 3. Backup and Restore Safeguard & Parameter Validation Tests
    // =========================================================================

    public function test_restore_script_refuses_execution_without_explicit_confirmation(): void
    {
        $scriptPath = $this->projectRoot.'/scripts/restore_database.sh';
        $this->assertFileExists($scriptPath);

        // Invocation lacking --confirm
        $process = new Process(['bash', $scriptPath, '--file', '/tmp/fake_backup.sql.gz', '--target-db', 'disposable_db']);
        $process->run();

        $this->assertEquals(1, $process->getExitCode(), 'Restore script must exit with error code 1 when --confirm is missing');
        $this->assertStringContainsString("You must supply '--confirm'", $process->getErrorOutput());
    }

    public function test_restore_script_rejects_unsafe_database_identifiers(): void
    {
        $scriptPath = $this->projectRoot.'/scripts/restore_database.sh';

        $unsafeIdentifiers = [
            'test; DROP DATABASE evil;',
            'test`--inject',
            'test database with spaces',
            'test"quoted',
            'test/slash',
        ];

        foreach ($unsafeIdentifiers as $badName) {
            $process = new Process(['bash', $scriptPath, '--file', '/tmp/fake.sql.gz', '--target-db', $badName, '--confirm']);
            $process->run();

            $this->assertEquals(1, $process->getExitCode(), "Restore script must reject unsafe database identifier: {$badName}");
            $this->assertStringContainsString('Unsafe target database identifier', $process->getErrorOutput());
        }
    }

    public function test_restore_script_fails_safely_when_backup_file_missing(): void
    {
        $scriptPath = $this->projectRoot.'/scripts/restore_database.sh';

        $process = new Process([
            'bash',
            $scriptPath,
            '--file',
            '/tmp/nonexistent_backup_file_'.uniqid().'.sql.gz',
            '--target-db',
            'valid_test_db',
            '--confirm',
        ]);
        $process->run();

        $this->assertEquals(1, $process->getExitCode(), 'Restore script must fail if backup file does not exist');
        $this->assertStringContainsString('Backup file does not exist', $process->getErrorOutput());
    }

    public function test_backup_script_rejects_unsafe_database_identifier(): void
    {
        $scriptPath = $this->projectRoot.'/scripts/backup_database.sh';
        $tmpEnv = tempnam(sys_get_temp_dir(), 'env_test_');

        file_put_contents($tmpEnv, "DB_CONNECTION=mysql\nDB_DATABASE=injected;drop\nDB_HOST=127.0.0.1\n");

        $process = new Process(['bash', $scriptPath, '-e', $tmpEnv]);
        $process->run();

        @unlink($tmpEnv);

        $this->assertEquals(1, $process->getExitCode(), 'Backup script must reject unsafe database identifiers');
        $this->assertStringContainsString('Unsafe database identifier', $process->getErrorOutput());
    }

    public function test_backup_script_fails_safely_when_env_file_missing(): void
    {
        $scriptPath = $this->projectRoot.'/scripts/backup_database.sh';

        $process = new Process(['bash', $scriptPath, '-e', '/tmp/nonexistent_env_'.uniqid()]);
        $process->run();

        $this->assertEquals(1, $process->getExitCode(), 'Backup script must fail if env file does not exist');
        $this->assertStringContainsString('Environment file not found', $process->getErrorOutput());
    }

    // =========================================================================
    // 4. Release Packaging Tooling Tests
    // =========================================================================

    public function test_release_packaging_script_enforces_security_exclusions_and_verification(): void
    {
        $scriptPath = $this->projectRoot.'/scripts/package_release.sh';
        $this->assertFileExists($scriptPath);
        $this->assertTrue(is_executable($scriptPath), 'Packaging script must be executable');

        // Help flag works cleanly
        $helpProcess = new Process(['bash', $scriptPath, '--help']);
        $helpProcess->run();
        $this->assertEquals(0, $helpProcess->getExitCode());
        $this->assertStringContainsString('Usage:', $helpProcess->getOutput());

        // Validate that forbidden patterns are defined in packaging script
        $scriptContent = file_get_contents($scriptPath);
        $this->assertStringContainsString("FORBIDDEN_PATTERNS=(", $scriptContent);
        $this->assertStringContainsString("node_modules/", $scriptContent);
        $this->assertStringContainsString("phpunit\\.xml", $scriptContent);
        $this->assertStringContainsString("\\.key$", $scriptContent);
    }

    // =========================================================================
    // 5. Truehost cPanel Split Directory Layout & Runtime Integrity Tests
    // =========================================================================

    public function test_public_front_controller_supports_split_cpanel_directory_layout(): void
    {
        $indexPath = $this->projectRoot.'/public/index.php';
        $this->assertFileExists($indexPath);

        $indexContent = file_get_contents($indexPath);
        $this->assertStringContainsString('medicare-hms-app', $indexContent, 'Front controller must support medicare-hms-app path');
        $this->assertStringContainsString('$appBasePath', $indexContent, 'Front controller must dynamically resolve $appBasePath');
    }

    public function test_bootstrap_supports_cpanel_public_path_binding(): void
    {
        $bootstrapPath = $this->projectRoot.'/bootstrap/app.php';
        $this->assertFileExists($bootstrapPath);

        $bootstrapContent = file_get_contents($bootstrapPath);
        $this->assertStringContainsString('medicare.kennedycheroben.co.ke', $bootstrapContent);
        $this->assertStringContainsString('LARAVEL_PUBLIC_PATH', $bootstrapContent);
        $this->assertStringContainsString('usePublicPath', $bootstrapContent);
    }

    public function test_compiled_vite_assets_and_manifest_exist_for_production(): void
    {
        $manifestPath = $this->projectRoot.'/public/build/manifest.json';
        $this->assertFileExists($manifestPath, 'Production Vite manifest.json must be present');

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $this->assertIsArray($manifest);
        $this->assertArrayHasKey('resources/css/app.css', $manifest);
        $this->assertArrayHasKey('resources/js/app.js', $manifest);

        $cssAsset = $this->projectRoot.'/public/build/'.$manifest['resources/css/app.css']['file'];
        $this->assertFileExists($cssAsset, 'Compiled CSS asset must exist on disk');
    }
}
