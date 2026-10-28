<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hms:create-admin 
                            {--name= : Name of the Super Administrator}
                            {--email= : Email address of the Super Administrator}
                            {--password= : Password (minimum 8 characters)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provision or promote a Super Administrator staff account securely via CLI';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Enter Administrator Name', 'Hospital SuperAdmin');
        $email = $this->option('email') ?: $this->ask('Enter Administrator Email');
        $password = $this->option('password') ?: $this->secret('Enter Strong Password (minimum 8 characters)');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => UserRole::SUPER_ADMIN,
                'is_active' => true,
            ]
        );

        AuditService::record(
            action: 'staff.admin_provisioned_cli',
            resourceType: 'user',
            resourceId: (string) $user->id,
            details: ['email' => $email, 'method' => 'artisan_cli'],
            outcome: 'success',
            actor: $user
        );

        $this->info("Super Administrator [{$user->email}] successfully provisioned.");

        return self::SUCCESS;
    }
}
