<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_performer_relationship_returns_associated_user(): void
    {
        $user = User::factory()->create();
        $log = AuditLog::create([
            'user_id' => $user->id,
            'actor_email' => $user->email,
            'action' => 'test.action',
            'details' => ['key' => 'value'],
        ]);

        $this->assertNotNull($log->performer);
        $this->assertEquals($user->id, $log->performer->id);
        $this->assertEquals($user->name, $log->performer->name);
    }

    public function test_audit_service_redacts_sensitive_credentials_and_tokens(): void
    {
        $user = User::factory()->create();
        $log = \App\Services\AuditService::record(
            action: 'auth.test',
            resourceType: 'system',
            resourceId: '1',
            details: [
                'password' => 'secret123',
                'api_key' => 'live_sk_abcdef',
                'access_token' => 'jwt_token_here',
                'pin' => '1234',
                'cvv' => '999',
                'mpesa_pin' => '0000',
                'nested' => [
                    'authorization' => 'Bearer sensitive_bearer_token',
                    'normal_key' => 'safe_value',
                ],
            ],
            outcome: 'success',
            actor: $user
        );

        $this->assertNotNull($log);
        $this->assertEquals('[REDACTED]', $log->details['password']);
        $this->assertEquals('[REDACTED]', $log->details['api_key']);
        $this->assertEquals('[REDACTED]', $log->details['access_token']);
        $this->assertEquals('[REDACTED]', $log->details['pin']);
        $this->assertEquals('[REDACTED]', $log->details['cvv']);
        $this->assertEquals('[REDACTED]', $log->details['mpesa_pin']);
        $this->assertEquals('[REDACTED]', $log->details['nested']['authorization']);
        $this->assertEquals('safe_value', $log->details['nested']['normal_key']);
    }
}
