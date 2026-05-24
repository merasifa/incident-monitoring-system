<?php

namespace Database\Factories;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Incident::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $severity = $this->faker->randomElement(IncidentSeverity::cases())->value;
        $status = $this->faker->randomElement(IncidentStatus::cases())->value;

        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'category' => $this->faker->randomElement(['Infrastructure', 'Performance', 'Payment', 'Reporting']),
            'severity' => $severity,
            'status' => $status,
            'created_by' => User::factory(),
            'due_at' => $this->faker->dateTimeBetween('now', '+7 days'),
            'last_status_changed_at' => now(),
            'resolved_at' => null,
        ];
    }
}
