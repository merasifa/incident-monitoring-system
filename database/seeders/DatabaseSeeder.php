<?php

namespace Database\Seeders;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@greenfields.com'],
            [
                'name' => 'Greenfields Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_admin' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'operator@greenfields.com'],
            [
                'name' => 'Greenfields Operator',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'is_admin' => false,
            ]
        );

        if (Incident::query()->exists()) {
            return;
        }

        Incident::insert([
            [
                'title' => 'Payment gateway timeout spike',
                'description' => 'Spike on timeout errors in checkout service.',
                'category' => 'Payment',
                'severity' => IncidentSeverity::Critical->value,
                'status' => IncidentStatus::Open->value,
                'created_by' => $admin->id,
                'due_at' => now()->addHours(2),
                'last_status_changed_at' => now(),
                'resolved_at' => null,
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(3),
            ],
            [
                'title' => 'Latency increase in API gateway',
                'description' => 'P95 above SLO for APAC region.',
                'category' => 'Performance',
                'severity' => IncidentSeverity::High->value,
                'status' => IncidentStatus::Investigating->value,
                'created_by' => $admin->id,
                'due_at' => now()->addHours(8),
                'last_status_changed_at' => now()->subHour(),
                'resolved_at' => null,
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHour(),
            ],
            [
                'title' => 'Disk usage warning node-3',
                'description' => 'Disk usage reached 82 percent.',
                'category' => 'Infrastructure',
                'severity' => IncidentSeverity::Medium->value,
                'status' => IncidentStatus::Open->value,
                'created_by' => $admin->id,
                'due_at' => now()->addDay(),
                'last_status_changed_at' => now()->subHours(2),
                'resolved_at' => null,
                'created_at' => now()->subHours(8),
                'updated_at' => now()->subHours(2),
            ],
            [
                'title' => 'Nightly report completed late',
                'description' => 'Batch report delayed by 20 minutes.',
                'category' => 'Reporting',
                'severity' => IncidentSeverity::Low->value,
                'status' => IncidentStatus::Resolved->value,
                'created_by' => $admin->id,
                'due_at' => now()->subDay(),
                'last_status_changed_at' => now()->subHours(10),
                'resolved_at' => now()->subHours(10),
                'created_at' => now()->subDay(),
                'updated_at' => now()->subHours(10),
            ],
        ]);
    }
}
