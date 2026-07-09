<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_health_endpoint_returns_successful_response(): void
    {
        $this->get('/up')->assertOk();
    }
}
