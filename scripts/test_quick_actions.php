<?php

use App\Services\IncidentSqlService;
use Illuminate\Support\Facades\DB;

$svc = app()->make(IncidentSqlService::class);
$actorId = DB::table('users')->where('email', 'admin@greenfields.com')->value('id') ?? DB::table('users')->value('id');

$incidentId = $svc->storeIncident([
    'title' => 'Quick Action Test',
    'description' => 'Testing assign/acknowledge/escalate',
    'category' => 'QA',
    'severity' => 'low',
    'status' => 'open',
    'due_at' => null,
], $actorId);

$assigned = $svc->assignIncident($incidentId, $actorId, $actorId);
$acknowledged = $svc->acknowledgeIncident($incidentId, $actorId);
$escalated = $svc->escalateIncident($incidentId, $actorId);
$final = $svc->findIncident($incidentId);
$activities = $svc->incidentActivities($incidentId)->map(fn($a) => [
    'event_type' => $a->event_type,
    'description' => $a->description,
])->values();

$svc->deleteIncident($incidentId);

$result = [
    'assigned' => [
        'assignee_id' => $assigned->assignee_id,
        'assignee_name' => $assigned->assignee_name,
        'status' => $assigned->status,
        'severity' => $assigned->severity,
    ],
    'acknowledged' => [
        'status' => $acknowledged->status,
        'severity' => $acknowledged->severity,
    ],
    'escalated' => [
        'status' => $escalated->status,
        'severity' => $escalated->severity,
    ],
    'final' => [
        'status' => $final->status,
        'severity' => $final->severity,
        'assignee_name' => $final->assignee_name,
    ],
    'activities' => $activities,
];

echo json_encode($result, JSON_PRETTY_PRINT);
