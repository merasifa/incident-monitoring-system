<?php

namespace Database\Seeders;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // ensure admin/operator exist
        $adminId = DB::table('users')->updateOrInsert(
            ['email' => 'admin@greenfields.com'],
            ['name' => 'Greenfields Admin', 'password' => Hash::make('password'), 'role' => 'admin', 'is_admin' => true]
        );

        $operatorId = DB::table('users')->updateOrInsert(
            ['email' => 'operator@greenfields.com'],
            ['name' => 'Greenfields Operator', 'password' => Hash::make('password'), 'role' => 'operator', 'is_admin' => false]
        );

        // Insert varied incidents
        $now = now();

        $incidents = [
            [
                'title' => 'Overdue critical - payments',
                'description' => 'Payment processing is failing',
                'category' => 'Payment',
                'severity' => IncidentSeverity::Critical->value,
                'status' => IncidentStatus::Open->value,
                'created_by' => DB::table('users')->where('email', 'admin@greenfields.com')->value('id'),
                'due_at' => $now->copy()->subDays(1),
                'last_status_changed_at' => $now->copy()->subDays(2),
                'resolved_at' => null,
            ],
            [
                'title' => 'Due soon - API latency',
                'description' => 'Scheduled maintenance window approaching',
                'category' => 'Maintenance',
                'severity' => IncidentSeverity::High->value,
                'status' => IncidentStatus::Open->value,
                'created_by' => DB::table('users')->where('email', 'operator@greenfields.com')->value('id'),
                'due_at' => $now->copy()->addHours(6),
                'last_status_changed_at' => $now->copy()->subHours(1),
                'resolved_at' => null,
            ],
            [
                'title' => 'Deadline warning - disk',
                'description' => 'Disk usage nearing limit',
                'category' => 'Infrastructure',
                'severity' => IncidentSeverity::Medium->value,
                'status' => IncidentStatus::Open->value,
                'created_by' => DB::table('users')->where('email', 'operator@greenfields.com')->value('id'),
                'due_at' => $now->copy()->addDays(2),
                'last_status_changed_at' => $now->copy()->subHours(4),
                'resolved_at' => null,
            ],
            [
                'title' => 'No due date - monitoring tweak',
                'description' => 'Minor alert, no due date',
                'category' => 'Monitoring',
                'severity' => IncidentSeverity::Low->value,
                'status' => IncidentStatus::Open->value,
                'created_by' => DB::table('users')->where('email', 'operator@greenfields.com')->value('id'),
                'due_at' => null,
                'last_status_changed_at' => $now->copy()->subHours(10),
                'resolved_at' => null,
            ],
            [
                'title' => 'Recently resolved - nightly report',
                'description' => 'Resolved incident for reporting delay',
                'category' => 'Reporting',
                'severity' => IncidentSeverity::Low->value,
                'status' => IncidentStatus::Resolved->value,
                'created_by' => DB::table('users')->where('email', 'admin@greenfields.com')->value('id'),
                'due_at' => $now->copy()->subDay(),
                'last_status_changed_at' => $now->copy()->subHours(26),
                'resolved_at' => $now->copy()->subHours(24),
            ],
        ];

        foreach ($incidents as $it) {
            DB::table('incidents')->insert(array_merge([
                'created_at' => DB::raw('NOW()'),
                'updated_at' => DB::raw('NOW()'),
            ], $it));
        }

        // Add activity logs to simulate notifications
        $latestIncidents = DB::table('incidents')->orderByDesc('id')->limit(5)->pluck('id')->values();
        foreach ($latestIncidents as $id) {
            DB::table('activity_logs')->insert([
                'incident_id' => $id,
                'user_id' => DB::table('users')->where('email', 'admin@greenfields.com')->value('id'),
                'event_type' => 'created',
                'description' => "Seeded activity for incident {$id}",
                'meta' => null,
                'created_at' => DB::raw('NOW()'),
                'updated_at' => DB::raw('NOW()'),
            ]);
        }
    }
}
