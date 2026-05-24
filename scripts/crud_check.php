<?php
// scripts/crud_check.php - run inside `php artisan tinker --execute "require 'scripts/crud_check.php'"`
use App\Services\IncidentSqlService;
use Illuminate\Support\Facades\DB;

$svc = app()->make(IncidentSqlService::class);
$userId = DB::table('users')->where('email', 'admin@greenfields.com')->value('id') ?? DB::table('users')->value('id');

// Create
$createdId = $svc->storeIncident([
    'title' => 'CI Create Test',
    'description' => 'Created by scripts/crud_check',
    'category' => 'QA',
    'severity' => 'medium',
    'status' => 'open',
    'due_at' => null,
], $userId);
$created = $svc->findIncident($createdId);

// Update -> investigating
$svc->updateIncident($createdId, ['status' => 'investigating', 'last_status_changed_at' => true]);
$afterInvestigating = $svc->findIncident($createdId);

// Update -> resolved
$svc->updateIncident($createdId, ['status' => 'resolved', 'last_status_changed_at' => true]);
$afterResolved = $svc->findIncident($createdId);

// Attempt reopen via service (this simulates bypassing request-level guard)
$svc->updateIncident($createdId, ['status' => 'open', 'last_status_changed_at' => true]);
$afterReopenRaw = DB::table('incidents')->where('id', $createdId)->first();

// Delete (soft)
$svc->deleteIncident($createdId);
$afterDeleteFind = $svc->findIncident($createdId);
$afterDeleteRaw = DB::table('incidents')->where('id', $createdId)->first();

// Workflow board
$workflow = $svc->workflowBoard();

// Summary & notifications
$summary = $svc->summary();
$notif = $svc->notificationSummary($userId);

$result = [
    'created' => (array) $created,
    'afterInvestigating' => (array) $afterInvestigating,
    'afterResolved' => (array) $afterResolved,
    'afterReopenRaw' => (array) $afterReopenRaw,
    'afterDeleteFind' => $afterDeleteFind ? (array) $afterDeleteFind : null,
    'afterDeleteRaw' => (array) $afterDeleteRaw,
    'workflow' => [
        'open' => count($workflow['open']),
        'investigating' => count($workflow['investigating']),
        'resolved_count' => $workflow['resolved_count'] ?? null,
    ],
    'summary' => (array) $summary,
    'notifications' => (array) $notif,
];

echo json_encode($result, JSON_PRETTY_PRINT);
