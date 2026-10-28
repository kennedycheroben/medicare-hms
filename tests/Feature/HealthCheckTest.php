<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Test that the minimal /health endpoint returns a successful JSON status.
     */
    public function test_health_check_endpoint_returns_successful_json_response(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
            ])
            ->assertJsonStructure([
                'status',
                'application',
                'environment',
                'timestamp',
            ]);
    }

    /**
     * Test that the default Laravel /up health endpoint returns a successful response.
     */
    public function test_laravel_up_endpoint_returns_successful_response(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }
}
