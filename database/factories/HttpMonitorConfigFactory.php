<?php

namespace Database\Factories;

use App\Models\HttpMonitorConfig;
use App\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HttpMonitorConfig>
 */
class HttpMonitorConfigFactory extends Factory
{
    protected $model = HttpMonitorConfig::class;

    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'url' => 'https://example.com',
            'method' => 'GET',
            'expected_status' => 200,
            'timeout_seconds' => 10,
            'keyword' => null,
        ];
    }
}
