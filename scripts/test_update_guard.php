<?php
$svc = app()->make(\App\Services\IncidentSqlService::class);
$resolvedId = DB::table('incidents')->where('status', 'resolved')->value('id');
try {
    $svc->updateIncident($resolvedId, ['status' => 'open']);
    echo "Updated\n";
} catch (\Throwable $e) {
    echo 'Exception: ' . $e->getMessage() . "\n";
}
