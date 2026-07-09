<?php

namespace Database\Factories;

use App\Enums\MonitorStatus;
use App\Enums\MonitorType;
use App\Models\Monitor;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Monitor>
 */
class MonitorFactory extends Factory
{
    protected $model = Monitor::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'type' => MonitorType::Http,
            'name' => fake()->words(3, true),
            'is_enabled' => true,
            'interval_seconds' => 60,
            'status' => MonitorStatus::Pending,
            'last_checked_at' => null,
            'last_status_change_at' => now(),
        ];
    }

    public function paused(): static
    {
        return $this->state(fn () => [
            'status' => MonitorStatus::Paused,
            'is_enabled' => false,
        ]);
    }

    public function due(): static
    {
        return $this->state(fn () => [
            'is_enabled' => true,
            'status' => MonitorStatus::Pending,
            'last_checked_at' => null,
        ]);
    }

    public function notDue(): static
    {
        return $this->state(fn () => [
            'is_enabled' => true,
            'status' => MonitorStatus::Up,
            'interval_seconds' => 3600,
            'last_checked_at' => now(),
        ]);
    }
}
