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
}
