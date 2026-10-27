<?php

namespace Tests\Feature;

use App\Models\Department;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class DemoModeSafetyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Department::exists()) {
            $this->seed(\Database\Seeders\HospitalDataSeeder::class);
        }
    }

    /**
     * Helper to recursively remove a temporary directory.
     */
    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_link($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    // =========================================================================
    // 1. Public Demonstration Safety & Disclaimer Tests
    // =========================================================================

    /**
     * Test that demo mode displays prominent software demonstration disclaimers across public pages.
     */
    public function test_demo_mode_displays_prominent_software_demonstration_disclaimers(): void
    {
        Config::set('hospital.is_demo', true);

        // Home page
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('Software Demonstration Notice');
        $homeResponse->assertSee('HMS Demonstration Platform');

        // Contact page
        $contactResponse = $this->get(route('contact'));
        $contactResponse->assertStatus(200);
        $contactResponse->assertSee('Software Demonstration Notice');
        $contactResponse->assertSee('Sample Switchboard');

        // Appointment booking page
        $appointmentResponse = $this->get(route('appointments.create'));
        $appointmentResponse->assertStatus(200);
        $appointmentResponse->assertSee('Demonstration Booking Notice');
        $appointmentResponse->assertSee('Simulated Appointment Request');
        $appointmentResponse->assertSee('Demonstration Workflow');

        // Departments page
        $departmentsResponse = $this->get(route('departments'));
        $departmentsResponse->assertStatus(200);
        $departmentsResponse->assertSee('Demonstration Departments Notice');

        // Services page
        $servicesResponse = $this->get(route('services'));
        $servicesResponse->assertStatus(200);
        $servicesResponse->assertSee('Demonstration Services Notice');

        // Doctors page
        $doctorsResponse = $this->get(route('doctors'));
        $doctorsResponse->assertStatus(200);
        $doctorsResponse->assertSee('Demonstration Roster Notice');

        // Patient Info page
        $patientInfoResponse = $this->get(route('patient-info'));
        $patientInfoResponse->assertStatus(200);
        $patientInfoResponse->assertSee('Demonstration Notice');

        // Terms of Service page
        $termsResponse = $this->get(route('terms'));
        $termsResponse->assertStatus(200);
        $termsResponse->assertSee('Demonstration Terms Notice');

        // Privacy Policy page
        $privacyResponse = $this->get(route('privacy'));
        $privacyResponse->assertStatus(200);
        $privacyResponse->assertSee('Demonstration Privacy Notice');

        // News page
        $newsResponse = $this->get(route('news'));
        $newsResponse->assertStatus(200);
        $newsResponse->assertSee('Demonstration Notice');
    }

    /**
     * Test that demo mode suppresses dialable emergency telephone links for fictional hotline numbers.
     */
    public function test_demo_mode_suppresses_clickable_fictional_emergency_telephone_links(): void
    {
        Config::set('hospital.is_demo', true);
        $emergencyPhone = config('hospital.emergency_phone');
        $emergencyTelAttr = 'href="tel:'.$emergencyPhone.'"';

        $pagesToCheck = [
            route('home'),
            route('contact'),
            route('appointments.create'),
            route('departments'),
        ];

        foreach ($pagesToCheck as $url) {
            $response = $this->get($url);
            $response->assertStatus(200);
            $response->assertDontSee($emergencyTelAttr, false);
        }
    }

    /**
     * Test that demo mode provides real-world national emergency dispatch guidance (999 / 112).
     */
    public function test_demo_mode_provides_national_emergency_dispatch_guidance(): void
    {
        Config::set('hospital.is_demo', true);

        // Home page
        $this->get(route('home'))
            ->assertStatus(200)
            ->assertSee('999 / 112')
            ->assertSee('Emergency Dispatch');

        // Contact page
        $this->get(route('contact'))
            ->assertStatus(200)
            ->assertSee('Call 999 / 112 (National)');

        // Appointment booking page
        $this->get(route('appointments.create'))
            ->assertStatus(200)
            ->assertSee('999 / 112');

        // Terms page
        $this->get(route('terms'))
            ->assertStatus(200)
            ->assertSee('999 / 112');
    }

    /**
     * Test that demo mode suppresses false accreditation and regulatory licensing claims.
     */
    public function test_demo_mode_suppresses_false_accreditation_claims(): void
    {
        Config::set('hospital.is_demo', true);

        // Services page should show simulated service instead of Level 1 Accredited or ISO 15189 Accredited
        $servicesResponse = $this->get(route('services'));
        $servicesResponse->assertStatus(200);
        $servicesResponse->assertDontSee('Level 1 Accredited');
        $servicesResponse->assertDontSee('ISO 15189 Accredited');
        $servicesResponse->assertSee('Simulated Emergency Service');
        $servicesResponse->assertSee('Diagnostic Laboratory');

        // Appointment booking page should not claim "Board-Certified" doctor
        $appointmentResponse = $this->get(route('appointments.create'));
        $appointmentResponse->assertStatus(200);
        $appointmentResponse->assertDontSee('Any Available Board-Certified Doctor');
        $appointmentResponse->assertSee('Any Available Specialist (Simulated)');

        // Home page should show Trauma Simulation Facility instead of Accredited Trauma Facility
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertDontSee('Accredited Trauma Facility');
        $homeResponse->assertSee('Trauma Simulation Facility');
    }

    // =========================================================================
    // 2. Executable Deployment Runbook & Asset Promotion Safety Tests
    // =========================================================================

    /**
     * Executable Test: Verify that asset promotion blocks silent overwrites when destination files differ.
     */
    public function test_asset_promotion_blocks_silent_overwrite_of_differing_files_without_explicit_approval(): void
    {
        $baseTemp = sys_get_temp_dir() . '/medicare_test_' . uniqid();
        $tempApp = $baseTemp . '/app';
        $tempWebRoot = $baseTemp . '/webroot';

        mkdir($tempApp . '/public/admin-assets', 0755, true);
        mkdir($tempApp . '/public/assets', 0755, true);
        file_put_contents($tempApp . '/public/assets/style.css', '/* release v2.0 */');
        file_put_contents($tempApp . '/public/index.php', '<?php echo "release v2.0";');
        file_put_contents($tempApp . '/public/robots.txt', 'User-agent: *');
        file_put_contents($tempApp . '/public/.htaccess', 'RewriteEngine On');

        mkdir($tempWebRoot, 0755, true);
        mkdir($tempWebRoot . '/assets', 0755, true);
        file_put_contents($tempWebRoot . '/assets/style.css', '/* existing custom style */');
        file_put_contents($tempWebRoot . '/index.php', '<?php echo "existing custom code";');

        $scriptPath = base_path('scripts/promote_release_assets.sh');
        $this->assertFileExists($scriptPath);

        // 1. Run WITHOUT explicit approval (ALLOW_REPLACE=0)
        $process = new Process([
            'bash',
            $scriptPath,
            '--app-dir', $tempApp,
            '--web-root', $tempWebRoot,
        ], null, ['ALLOW_REPLACE' => '0']);
        $process->run();

        // Must fail with non-zero exit code
        $this->assertFalse($process->isSuccessful(), 'Promotion must fail when collisions exist without explicit approval.');
        $this->assertStringContainsString('SAFETY STOP', $process->getErrorOutput());
        $this->assertStringContainsString('ALLOW_REPLACE=1', $process->getErrorOutput());

        // Verify existing destination file and directory were NOT overwritten
        $this->assertEquals(
            '<?php echo "existing custom code";',
            file_get_contents($tempWebRoot . '/index.php'),
            'Existing destination file must not be silently overwritten.'
        );
        $this->assertEquals(
            '/* existing custom style */',
            file_get_contents($tempWebRoot . '/assets/style.css'),
            'Existing destination directory must not be silently overwritten.'
        );

        // 2. Run WITH explicit approval (ALLOW_REPLACE=1)
        $approvedProcess = new Process([
            'bash',
            $scriptPath,
            '--app-dir', $tempApp,
            '--web-root', $tempWebRoot,
            '--confirm-replace',
        ], null, ['ALLOW_REPLACE' => '1']);
        $approvedProcess->run();

        $this->assertTrue(
            $approvedProcess->isSuccessful(),
            'Promotion must succeed once operator explicitly approves replacement: ' . $approvedProcess->getErrorOutput()
        );
        $this->assertEquals(
            '<?php echo "release v2.0";',
            file_get_contents($tempWebRoot . '/index.php'),
            'Destination file must be updated to staged release after approval.'
        );
        $this->assertEquals(
            '/* release v2.0 */',
            file_get_contents($tempWebRoot . '/assets/style.css'),
            'Destination directory content must be updated to staged release after approval.'
        );

        // 3. Verify backup retention: previous live asset directory must be preserved, not deleted immediately
        $prevBackups = glob($tempWebRoot . '/assets_prev_*');
        $this->assertNotEmpty($prevBackups, 'Previous asset directory backup must exist on disk.');
        $this->assertFileExists($prevBackups[0] . '/style.css');
        $this->assertEquals(
            '/* existing custom style */',
            file_get_contents($prevBackups[0] . '/style.css'),
            'Previous asset directory backup must retain the prior content for rollback.'
        );

        // Verify web root backup was created and retained
        $webrootBackups = glob($baseTemp . '/backup_webroot_*');
        $this->assertNotEmpty($webrootBackups, 'Full web root backup must exist on disk.');
        $this->assertStringContainsString('RETAINED BACKUPS FOR ROLLBACK', $approvedProcess->getOutput());
        $this->assertStringContainsString('permanently preserved until a human operator', $approvedProcess->getOutput());

        $this->removeDirectory($baseTemp);
    }

    /**
     * Executable Test: Verify that asset promotion preserves cPanel-generated .htaccess handlers.
     */
    public function test_asset_promotion_preserves_cpanel_htaccess_handlers(): void
    {
        $baseTemp = sys_get_temp_dir() . '/medicare_htaccess_' . uniqid();
        $tempApp = $baseTemp . '/app';
        $tempWebRoot = $baseTemp . '/webroot';

        mkdir($tempApp . '/public', 0755, true);
        file_put_contents($tempApp . '/public/index.php', '<?php echo "v2";');
        file_put_contents($tempApp . '/public/.htaccess', "RewriteEngine On\nRewriteRule ^ index.php [L]");

        mkdir($tempWebRoot, 0755, true);
        $cpanelHandler = "# php -- BEGIN cPanel-generated handler do not edit\nAddHandler application/x-httpd-ea-php83 .php\n# php -- END cPanel-generated handler\n";
        file_put_contents($tempWebRoot . '/.htaccess', $cpanelHandler);

        $scriptPath = base_path('scripts/promote_release_assets.sh');
        $process = new Process([
            'bash',
            $scriptPath,
            '--app-dir', $tempApp,
            '--web-root', $tempWebRoot,
            '--confirm-replace',
        ], null, ['ALLOW_REPLACE' => '1']);
        $process->run();

        $this->assertTrue($process->isSuccessful(), 'Promotion should succeed: ' . $process->getErrorOutput());

        $promotedHtaccess = file_get_contents($tempWebRoot . '/.htaccess');
        $this->assertStringContainsString('# php -- BEGIN cPanel-generated handler', $promotedHtaccess);
        $this->assertStringContainsString('AddHandler application/x-httpd-ea-php83 .php', $promotedHtaccess);
        $this->assertStringContainsString('RewriteEngine On', $promotedHtaccess);

        $this->removeDirectory($baseTemp);
    }

    // =========================================================================
    // 3. Executable Storage Symlink & Permission Tests
    // =========================================================================

    /**
     * Executable Test: Verify that storage symlink configurator refuses to silently replace an unexpected active symlink.
     */
    public function test_storage_symlink_blocks_automatic_replacement_of_unexpected_active_target(): void
    {
        $baseTemp = sys_get_temp_dir() . '/medicare_symlink_' . uniqid();
        $tempApp = $baseTemp . '/app';
        $tempWebRoot = $baseTemp . '/webroot';
        $otherActiveTarget = $baseTemp . '/other_mount';

        mkdir($tempApp . '/storage/app/public', 0755, true);
        mkdir($tempApp . '/bootstrap/cache', 0755, true);
        mkdir($tempWebRoot, 0755, true);
        mkdir($otherActiveTarget, 0755, true);

        // Create symlink pointing to an unexpected active target
        symlink($otherActiveTarget, $tempWebRoot . '/storage');

        $scriptPath = base_path('scripts/configure_storage_symlink.sh');
        $this->assertFileExists($scriptPath);

        // 1. Execute WITHOUT operator confirmation
        $process = new Process([
            'bash',
            $scriptPath,
            '--app-dir', $tempApp,
            '--web-root', $tempWebRoot,
            '--skip-chmod',
        ]);
        $process->run();

        // Must fail and block automatic replacement
        $this->assertFalse(
            $process->isSuccessful(),
            'Storage script must stop for human review when symlink points to unexpected active target.'
        );
        $this->assertStringContainsString('UNEXPECTED TARGET', $process->getErrorOutput());
        $this->assertStringContainsString('CONFIRM_STALE_SYMLINK=1', $process->getErrorOutput());

        // Verify symlink was NOT clobbered
        $this->assertEquals(
            $otherActiveTarget,
            readlink($tempWebRoot . '/storage'),
            'Unexpected active symlink must remain untouched without explicit confirmation.'
        );

        // 2. Execute WITH explicit operator confirmation (CONFIRM_STALE_SYMLINK=1)
        $approvedProcess = new Process([
            'bash',
            $scriptPath,
            '--app-dir', $tempApp,
            '--web-root', $tempWebRoot,
            '--confirm-stale-symlink',
            '--skip-chmod',
        ], null, ['CONFIRM_STALE_SYMLINK' => '1']);
        $approvedProcess->run();

        $this->assertTrue(
            $approvedProcess->isSuccessful(),
            'Storage script must succeed once operator confirms stale symlink replacement: ' . $approvedProcess->getErrorOutput()
        );
        $this->assertEquals(
            $tempApp . '/storage/app/public',
            readlink($tempWebRoot . '/storage'),
            'Symlink must be updated to application storage target after explicit confirmation.'
        );

        $this->removeDirectory($baseTemp);
    }

    /**
     * Executable Test: Verify that storage symlink configurator blocks silent replacement of physical directory.
     */
    public function test_storage_symlink_blocks_silent_replacement_of_physical_directory(): void
    {
        $baseTemp = sys_get_temp_dir() . '/medicare_dir_' . uniqid();
        $tempApp = $baseTemp . '/app';
        $tempWebRoot = $baseTemp . '/webroot';

        mkdir($tempApp . '/storage/app/public', 0755, true);
        mkdir($tempApp . '/bootstrap/cache', 0755, true);
        mkdir($tempWebRoot . '/storage', 0755, true);
        file_put_contents($tempWebRoot . '/storage/patient_scan.pdf', 'fake PDF content');

        $scriptPath = base_path('scripts/configure_storage_symlink.sh');

        // 1. Run WITHOUT confirmation
        $process = new Process([
            'bash',
            $scriptPath,
            '--app-dir', $tempApp,
            '--web-root', $tempWebRoot,
            '--skip-chmod',
        ]);
        $process->run();

        $this->assertFalse(
            $process->isSuccessful(),
            'Storage script must stop for human review when destination is a physical directory.'
        );
        $this->assertStringContainsString('PHYSICAL DIRECTORY', $process->getErrorOutput());
        $this->assertStringContainsString('CONFIRM_DIR_MIGRATION=1', $process->getErrorOutput());
        $this->assertTrue(
            is_dir($tempWebRoot . '/storage') && ! is_link($tempWebRoot . '/storage'),
            'Physical directory must not be removed without confirmation.'
        );

        // 2. Run WITH confirmation
        $approvedProcess = new Process([
            'bash',
            $scriptPath,
            '--app-dir', $tempApp,
            '--web-root', $tempWebRoot,
            '--confirm-dir-migration',
            '--skip-chmod',
        ], null, ['CONFIRM_DIR_MIGRATION' => '1']);
        $approvedProcess->run();

        $this->assertTrue(
            $approvedProcess->isSuccessful(),
            'Storage script must succeed with confirmation: ' . $approvedProcess->getErrorOutput()
        );
        $this->assertTrue(is_link($tempWebRoot . '/storage'), 'Destination must now be a symlink.');

        $this->removeDirectory($baseTemp);
    }

    /**
     * Executable Test: Verify least-privilege permissions (755 directories, 644 files) without broad 775.
     */
    public function test_storage_script_enforces_least_privilege_permissions_without_broad_775(): void
    {
        $baseTemp = sys_get_temp_dir() . '/medicare_perms_' . uniqid();
        $tempApp = $baseTemp . '/app';
        $tempWebRoot = $baseTemp . '/webroot';

        mkdir($tempApp . '/storage/framework/views', 0777, true);
        mkdir($tempApp . '/bootstrap/cache', 0777, true);
        file_put_contents($tempApp . '/storage/framework/views/test.php', '<?php');
        file_put_contents($tempApp . '/.env', 'APP_ENV=production');
        chmod($tempApp . '/.env', 0666);

        mkdir($tempWebRoot, 0755, true);

        $scriptPath = base_path('scripts/configure_storage_symlink.sh');
        $process = new Process([
            'bash',
            $scriptPath,
            '--app-dir', $tempApp,
            '--web-root', $tempWebRoot,
        ]);
        $process->run();

        $this->assertTrue($process->isSuccessful(), 'Storage script must succeed: ' . $process->getErrorOutput());

        // Check directory permission is 755 (octal 0755)
        $dirPerms = substr(sprintf('%o', fileperms($tempApp . '/storage')), -4);
        $this->assertEquals('0755', $dirPerms, 'Directories must be 0755 for least privilege under suPHP.');

        // Check file permission is 644 (octal 0644)
        $filePerms = substr(sprintf('%o', fileperms($tempApp . '/storage/framework/views/test.php')), -4);
        $this->assertEquals('0644', $filePerms, 'Files must be 0644 for least privilege.');

        // Check .env is 600
        $envPerms = substr(sprintf('%o', fileperms($tempApp . '/.env')), -4);
        $this->assertEquals('0600', $envPerms, '.env must be 0600.');

        $this->removeDirectory($baseTemp);
    }

    /**
     * Anti-pattern Test: Ensure runbook forbids unsafe staged-copy overwrites and unconditional symlink replacement.
     */
    public function test_runbook_forbids_unsafe_overwrite_and_automatic_replacement_patterns(): void
    {
        $runbookPath = base_path('DEPLOYMENT_RUNBOOK.md');
        $this->assertFileExists($runbookPath);
        $runbookContent = file_get_contents($runbookPath);

        // 1. Must NOT contain unsafe staged copy pattern
        $this->assertStringNotContainsString(
            'cp -a "${STAGING_DIR}/." "${WEB_ROOT}/"',
            $runbookContent,
            'Runbook must not contain the unsafe staged-copy pattern cp -a "${STAGING_DIR}/." "${WEB_ROOT}/".'
        );
        $this->assertStringNotContainsString(
            'cp -a "${STAGING_DIR}/."',
            $runbookContent,
            'Runbook must not instruct blind copy of staging directory into web root.'
        );

        // 2. Must NOT contain broad 775 permissions
        $this->assertStringNotContainsString(
            'chmod -R 775',
            $runbookContent,
            'Runbook must not recommend unnecessarily broad 775 permissions.'
        );

        // 3. Must document explicit approval controls
        $this->assertStringContainsString('ALLOW_REPLACE=1', $runbookContent);
        $this->assertStringContainsString('CONFIRM_STALE_SYMLINK=1', $runbookContent);
        $this->assertStringContainsString('CONFIRM_DIR_MIGRATION=1', $runbookContent);

        // 4. Must reference least privilege permissions
        $this->assertStringContainsString('755', $runbookContent);
        $this->assertStringContainsString('644', $runbookContent);
        $this->assertStringContainsString('600', $runbookContent);

        // 5. Must enforce deployment from fix/release-readiness-audit and forbid main
        $this->assertStringContainsString('fix/release-readiness-audit', $runbookContent);
        $this->assertStringContainsString('deploy from `main`', $runbookContent);
        $this->assertStringNotContainsString('git clone -b main', $runbookContent);

        // 6. Must not contain duplicate manual asset promotion procedure bypassing scripts/promote_release_assets.sh
        $this->assertStringNotContainsString('Option 2: Step-by-Step SSH Procedure', $runbookContent);
        $this->assertStringContainsString('promote_release_assets.sh', $runbookContent);
    }
}
