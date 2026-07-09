<?php

namespace Database\Factories;

use App\Enums\IncidentCause;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'project_id' => fn (array $attributes) => Monitor::query()
                ->findOrFail($attributes['monitor_id'])
                ->project_id,
            'status' => IncidentStatus::Open,
            'cause' => IncidentCause::HttpFailure,
            'summary' => 'HTTP check failed',
            'opened_at' => now(),
        ];
    }

    public function acknowledged(): static
    {
        return $this->state(fn () => [
            'status' => IncidentStatus::Acknowledged,
            'acknowledged_at' => now(),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => IncidentStatus::Resolved,
            'resolved_at' => now(),
        ]);
    }
}
