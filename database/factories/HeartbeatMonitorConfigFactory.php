<?php

namespace Database\Factories;

use App\Models\HeartbeatMonitorConfig;
use App\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HeartbeatMonitorConfig>
 */
class HeartbeatMonitorConfigFactory extends Factory
{
    protected $model = HeartbeatMonitorConfig::class;

    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'token' => HeartbeatMonitorConfig::generateToken(),
            'expected_every_seconds' => 300,
            'grace_seconds' => 60,
            'last_heartbeat_at' => null,
        ];
    }
}
