<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IncidentSqlService
{
    public function baseQuery(): Builder
    {
        return DB::table('incidents as i')
            ->leftJoin('users as u', 'u.id', '=', 'i.created_by')
            ->leftJoin('users as au', 'au.id', '=', 'i.assignee_id')
            ->whereNull('i.deleted_at')
            ->select([
                'i.id',
                'i.title',
                'i.description',
                'i.category',
                'i.severity',
                'i.status',
                'i.created_by',
                'u.name as created_by_name',
                'i.assignee_id',
                'au.name as assignee_name',
                'i.due_at',
                'i.last_status_changed_at',
                'i.resolved_at',
                'i.created_at',
                'i.updated_at',
            ])
            ->selectRaw("CASE i.severity WHEN 'critical' THEN 'Critical' WHEN 'high' THEN 'High' WHEN 'medium' THEN 'Medium' WHEN 'low' THEN 'Low' END as severity_label")
            ->selectRaw("CASE i.status WHEN 'open' THEN 'Open' WHEN 'investigating' THEN 'Investigating' WHEN 'resolved' THEN 'Resolved' END as status_label")
            ->selectRaw("CASE WHEN i.severity IN ('critical', 'high') AND i.status <> 'resolved' THEN 1 ELSE 0 END as is_urgent")
            ->selectRaw("CASE WHEN i.due_at IS NOT NULL AND i.due_at < NOW() AND i.status <> 'resolved' THEN 1 ELSE 0 END as is_overdue")
            // due category helps with priority and UI badges: overdue, due_soon (<24h), deadline_warning (24-72h), normal, none
            ->selectRaw("CASE WHEN i.due_at IS NULL THEN 'none' WHEN i.due_at < NOW() AND i.status <> 'resolved' THEN 'overdue' WHEN i.due_at <= DATE_ADD(NOW(), INTERVAL 24 HOUR) AND i.status <> 'resolved' THEN 'due_soon' WHEN i.due_at <= DATE_ADD(NOW(), INTERVAL 72 HOUR) AND i.status <> 'resolved' THEN 'deadline_warning' ELSE 'normal' END as due_category")
            ->selectRaw("TIMESTAMPDIFF(HOUR, NOW(), i.due_at) as due_in_hours");
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['severity'] ?? null, fn(Builder $builder, string $severity) => $builder->where('i.severity', $severity))
            ->when($filters['status'] ?? null, fn(Builder $builder, string $status) => $builder->where('i.status', $status))
            ->when($filters['category'] ?? null, fn(Builder $builder, string $category) => $builder->where('i.category', $category))
            ->when($filters['date'] ?? null, function (Builder $builder, string $date) {
                $start = Carbon::parse($date)->startOfDay();
                $end = Carbon::parse($date)->endOfDay();

                return $builder->whereBetween('i.created_at', [$start, $end]);
            });
    }

    public function applyPrioritySort(Builder $query): Builder
    {
        // Custom priority ordering to combine severity + due date + status according to requirements,
        // promote due_soon for medium as well so near-deadline items bubble up.
        return $query
            ->orderByRaw(
                "CASE 
                    WHEN i.severity = 'critical' AND i.due_at < NOW() AND i.status <> 'resolved' THEN 1 
                    WHEN i.severity = 'critical' AND i.due_at <= DATE_ADD(NOW(), INTERVAL 24 HOUR) AND i.status <> 'resolved' THEN 2 
                    WHEN i.severity = 'high' AND i.due_at < NOW() AND i.status <> 'resolved' THEN 3 
                    WHEN i.severity = 'high' AND i.due_at <= DATE_ADD(NOW(), INTERVAL 24 HOUR) AND i.status <> 'resolved' THEN 4 
                    WHEN i.severity = 'medium' AND i.due_at < NOW() AND i.status <> 'resolved' THEN 5 
                    WHEN i.severity = 'medium' AND i.due_at <= DATE_ADD(NOW(), INTERVAL 24 HOUR) AND i.status <> 'resolved' THEN 6 
                    WHEN i.status = 'open' AND i.last_status_changed_at IS NOT NULL THEN 7 
                    WHEN i.severity = 'high' THEN 8 
                    WHEN i.severity = 'medium' THEN 9 
                    WHEN i.severity = 'low' THEN 10 
                    ELSE 11 END"
            )
            // For open incidents, prefer ones that have been open the longest
            ->orderByRaw("CASE WHEN i.status = 'open' THEN i.last_status_changed_at ELSE NULL END ASC")
            ->orderByDesc('i.updated_at')
            ->orderByDesc('i.id');
    }

    public function paginateIncidents(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $paginator = $this->applyPrioritySort(
            $this->applyFilters($this->baseQuery(), $filters)
        )->paginate($perPage)->withQueryString();

        $paginator->setCollection($paginator->getCollection()->map(fn(object $row) => $this->hydrateIncident($row)));

        return $paginator;
    }

    public function incidentList(int $limit = 10): Collection
    {
        return $this->baseQuery()
            ->orderByRaw('COALESCE(i.last_status_changed_at, i.updated_at, i.created_at) DESC')
            ->orderByDesc('i.updated_at')
            ->orderByDesc('i.id')
            ->limit($limit)
            ->get()
            ->map(fn(object $row) => $this->hydrateIncident($row));
    }

    /**
     * Search incidents with simple query and filters.
     * Supports searching by ID (e.g. "INC-0001" or numeric), title, and category.
     */
    public function searchIncidents(array $filters = [], ?string $q = null, int $perPage = 12): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        // allow quick id search like "INC-0003" or just number
        if (! empty($q)) {
            $trim = trim($q);
            if (preg_match('/^INC-?(\d+)$/i', $trim, $m)) {
                $query->where('i.id', (int) $m[1]);
            } elseif (is_numeric($trim)) {
                $query->where('i.id', (int) $trim);
            } else {
                $like = '%' . str_replace(' ', '%', $trim) . '%';
                $query->where(function (Builder $b) use ($like) {
                    $b->where('i.title', 'like', $like)
                        ->orWhere('i.category', 'like', $like);
                });
            }
        }

        $query = $this->applyFilters($query, $filters);

        $paginator = $this->applyPrioritySort($query)->paginate($perPage)->withQueryString();
        $paginator->setCollection($paginator->getCollection()->map(fn(object $row) => $this->hydrateIncident($row)));

        return $paginator;
    }

    /**
     * Notification summary used for top-bar badge and quick list.
     */
    public function notificationSummary(?int $userId = null): object
    {
        $summary = (object) [];

        $summary->overdue_count = DB::table('incidents as i')
            ->whereNull('i.deleted_at')
            ->where('i.status', '<>', 'resolved')
            ->whereNotNull('i.due_at')
            ->where('i.due_at', '<', DB::raw('NOW()'))
            ->count();

        $summary->due_soon_count = DB::table('incidents as i')
            ->whereNull('i.deleted_at')
            ->where('i.status', '<>', 'resolved')
            ->whereNotNull('i.due_at')
            ->where('i.due_at', '<=', DB::raw("DATE_ADD(NOW(), INTERVAL 24 HOUR)"))
            ->where('i.due_at', '>=', DB::raw('NOW()'))
            ->count();

        $summary->new_critical_count = DB::table('incidents as i')
            ->whereNull('i.deleted_at')
            ->where('i.status', '<>', 'resolved')
            ->where('i.severity', 'critical')
            ->where('i.created_at', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 24 HOUR)'))
            ->count();

        // supply small lists for quick menu
        $topOverdue = $this->applyPrioritySort($this->baseQuery()->whereRaw("i.due_at < NOW() AND i.status <> 'resolved'"))
            ->limit(5)
            ->get()
            ->map(fn(object $row) => $this->hydrateIncident($row));

        $topCritical = $this->applyPrioritySort($this->baseQuery()->where('i.severity', 'critical')->where('i.status', '<>', 'resolved'))
            ->limit(5)
            ->get()
            ->map(fn(object $row) => $this->hydrateIncident($row));

        // attach per-user read flag if userId provided
        if ($userId) {
            $reads = DB::table('activity_logs')
                ->where('event_type', 'notification_read')
                ->where('user_id', $userId)
                ->pluck('incident_id')
                ->filter()
                ->map(fn($v) => (int) $v)
                ->values()
                ->all();

            $summary->top_overdue = $topOverdue->map(fn($it) => (object) array_merge((array) $it, ['read' => in_array($it->id, $reads, true)]));
            $summary->top_critical = $topCritical->map(fn($it) => (object) array_merge((array) $it, ['read' => in_array($it->id, $reads, true)]));
        } else {
            $summary->top_overdue = $topOverdue;
            $summary->top_critical = $topCritical;
        }

        return $summary;
    }

    public function workflowBoard(): Collection
    {
        $all = $this->applyPrioritySort($this->baseQuery())
            ->get()
            ->map(fn(object $row) => $this->hydrateIncident($row));

        // Keep priority order for complete incidents, then append incomplete ones at the bottom
        $complete = $all->filter(fn($r) => empty($r->incomplete) || $r->incomplete === false)->values();
        $incomplete = $all->filter(fn($r) => ! empty($r->incomplete) && $r->incomplete === true)->values();

        $open = $complete->where('status', 'open')->values()->concat($incomplete->where('status', 'open')->values());
        $investigating = $complete->where('status', 'investigating')->values()->concat($incomplete->where('status', 'investigating')->values());

        $recentResolved = $this->baseQuery()
            ->where('i.status', 'resolved')
            ->orderByDesc('i.resolved_at')
            ->limit(3)
            ->get()
            ->map(fn(object $row) => $this->hydrateIncident($row));

        $resolvedCount = DB::table('incidents')->whereNull('deleted_at')->where('status', 'resolved')->count();

        return collect([
            'open' => $open,
            'investigating' => $investigating,
            'recent_resolved' => $recentResolved,
            'resolved_count' => $resolvedCount,
        ]);
    }

    public function criticalAlerts(int $limit = 5): Collection
    {
        return $this->applyPrioritySort(
            $this->baseQuery()->where('i.severity', 'critical')->where('i.status', '<>', 'resolved')
        )
            ->limit($limit)
            ->get()
            ->map(fn(object $row) => $this->hydrateIncident($row));
    }

    public function pinnedCritical(): ?object
    {
        $incident = $this->applyPrioritySort(
            $this->baseQuery()->where('i.severity', 'critical')->where('i.status', '<>', 'resolved')
        )->first();

        return $incident ? $this->hydrateIncident($incident) : null;
    }

    public function summary(): object
    {
        return DB::table('incidents as i')
            ->whereNull('i.deleted_at')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN i.severity = 'critical' AND i.status <> 'resolved' THEN 1 ELSE 0 END) as critical_open")
            ->selectRaw("SUM(CASE WHEN i.status = 'investigating' THEN 1 ELSE 0 END) as investigating")
            ->selectRaw("SUM(CASE WHEN i.status = 'resolved' AND DATE(i.resolved_at) = CURRENT_DATE() THEN 1 ELSE 0 END) as resolved_today")
            ->selectRaw("SUM(CASE WHEN i.due_at IS NOT NULL AND i.due_at < NOW() AND i.status <> 'resolved' THEN 1 ELSE 0 END) as overdue_count")
            ->selectRaw("SUM(CASE WHEN i.due_at IS NOT NULL AND i.due_at <= DATE_ADD(NOW(), INTERVAL 24 HOUR) AND i.due_at >= NOW() AND i.status <> 'resolved' THEN 1 ELSE 0 END) as due_soon_count")
            ->selectRaw("SUM(CASE WHEN i.severity IN ('critical','high') AND i.status <> 'resolved' THEN 1 ELSE 0 END) as open_urgent")
            ->first();
    }

    public function trendData(int $days = 7): Collection
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $trend = DB::table('incidents as i')
            ->selectRaw('DATE(i.created_at) as day, COUNT(*) as total')
            ->whereNull('i.deleted_at')
            ->whereBetween('i.created_at', [$start, $end])
            ->groupBy(DB::raw('DATE(i.created_at)'))
            ->orderBy('day')
            ->pluck('total', 'day');

        return collect(range($days - 1, 0))->map(function (int $diff) use ($trend) {
            $day = now()->subDays($diff)->toDateString();

            return [
                'day' => $day,
                'label' => now()->subDays($diff)->format('d M'),
                'value' => (int) ($trend[$day] ?? 0),
            ];
        });
    }

    public function recentActivities(int $limit = 8): Collection
    {
        return DB::table('activity_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->leftJoin('incidents as i', 'i.id', '=', 'a.incident_id')
            ->select([
                'a.id',
                'a.event_type',
                'a.description',
                'a.meta',
                'a.created_at',
                'u.name as user_name',
                'i.title as incident_title',
            ])
            ->orderByRaw('(a.created_at > NOW()) ASC')
            ->orderByDesc('a.created_at')
            ->limit($limit)
            ->get()
            ->map(fn(object $row) => $this->hydrateActivity($row))
            ->sort(function (object $a, object $b) {
                $now = now();
                $aFuture = $a->created_at > $now;
                $bFuture = $b->created_at > $now;

                if ($aFuture !== $bFuture) {
                    return $aFuture ? 1 : -1; // future items go after
                }

                // both on same side of now: newest first
                return $b->created_at <=> $a->created_at;
            })->values();
    }

    public function incidentActivities(int $incidentId): Collection
    {
        return DB::table('activity_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->select([
                'a.id',
                'a.event_type',
                'a.description',
                'a.meta',
                'a.created_at',
                'u.name as user_name',
            ])
            ->where('a.incident_id', $incidentId)
            ->orderByRaw('(a.created_at > NOW()) ASC')
            ->orderByDesc('a.created_at')
            ->get()
            ->map(fn(object $row) => $this->hydrateActivity($row))
            ->sort(function (object $a, object $b) {
                $now = now();
                $aFuture = $a->created_at > $now;
                $bFuture = $b->created_at > $now;

                if ($aFuture !== $bFuture) {
                    return $aFuture ? 1 : -1;
                }

                return $b->created_at <=> $a->created_at;
            })->values();
    }

    public function findIncident(int $incidentId): ?object
    {
        $incident = $this->baseQuery()->where('i.id', $incidentId)->first();

        return $incident ? $this->hydrateIncident($incident) : null;
    }

    public function storeIncident(array $data, int $userId): int
    {
        return (int) DB::table('incidents')->insertGetId([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'],
            'severity' => $data['severity'],
            // default status to 'open' if not provided
            'status' => $data['status'] ?? 'open',
            'created_by' => $userId,
            'assignee_id' => $data['assignee_id'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            // use DB server time for timestamps to keep consistency with DB-side queries
            'last_status_changed_at' => ($data['last_status_changed_at'] ?? null) ? DB::raw('NOW()') : null,
            'resolved_at' => ($data['status'] ?? null) === 'resolved' ? DB::raw('NOW()') : ($data['resolved_at'] ?? null),
            'created_at' => DB::raw('NOW()'),
            'updated_at' => DB::raw('NOW()'),
        ]);
    }

    public function updateIncident(int $incidentId, array $data): void
    {
        // Prevent updates to incidents that are already resolved to enforce immutability at service level
        $currentStatus = DB::table('incidents')->where('id', $incidentId)->value('status');
        if ($currentStatus === 'resolved') {
            throw new \RuntimeException('Incident is resolved and cannot be modified.');
        }

        // Only update status-related fields to preserve original incident details (title/desc/category/severity/due date)
        $update = [
            'status' => $data['status'],
            'updated_at' => DB::raw('NOW()'),
        ];

        if (! empty($data['last_status_changed_at'])) {
            $update['last_status_changed_at'] = DB::raw('NOW()');
        }

        if (isset($data['status']) && $data['status'] === 'resolved') {
            // if resolved, set resolved_at if not already set
            $update['resolved_at'] = DB::raw('NOW()');
        } elseif (isset($data['status']) && $data['status'] !== 'resolved') {
            // clearing resolved_at is not permitted here to preserve history; only allow setting when transitioning to resolved
        }

        DB::table('incidents')
            ->where('id', $incidentId)
            ->update($update);
    }

    public function assignIncident(int $incidentId, ?int $assigneeId, int $actorId): object
    {
        $incident = $this->baseQuery()->where('i.id', $incidentId)->first();
        if (! $incident) {
            throw new \RuntimeException('Incident not found.');
        }

        if ($incident->status === 'resolved') {
            throw new \RuntimeException('Incident is resolved and cannot be modified.');
        }

        if ($assigneeId !== null && ! DB::table('users')->where('id', $assigneeId)->exists()) {
            throw new \RuntimeException('Assignee not found.');
        }

        DB::table('incidents')
            ->where('id', $incidentId)
            ->update([
                'assignee_id' => $assigneeId,
                'updated_at' => DB::raw('NOW()'),
            ]);

        $assigneeName = $assigneeId ? DB::table('users')->where('id', $assigneeId)->value('name') : 'Unassigned';

        $this->logActivity(
            $incidentId,
            $actorId,
            'incident_assigned',
            "Incident {$incident->title} assigned to {$assigneeName}",
            ['assignee_id' => $assigneeId, 'assignee_name' => $assigneeName]
        );

        return $this->findIncident($incidentId);
    }

    public function acknowledgeIncident(int $incidentId, int $actorId): object
    {
        $incident = $this->findIncident($incidentId);
        if (! $incident) {
            throw new \RuntimeException('Incident not found.');
        }

        if ($incident->status === 'resolved') {
            throw new \RuntimeException('Incident is resolved and cannot be modified.');
        }

        $description = "Incident {$incident->title} acknowledged";

        if ($incident->status === 'open') {
            $this->updateIncident($incidentId, ['status' => 'investigating', 'last_status_changed_at' => true]);
            $incident = $this->findIncident($incidentId);
            $description = "Incident {$incident->title} acknowledged and moved to investigating";
        }

        $this->logActivity(
            $incidentId,
            $actorId,
            'incident_acknowledged',
            $description
        );

        return $incident;
    }

    public function escalateIncident(int $incidentId, int $actorId): object
    {
        $incident = $this->findIncident($incidentId);
        if (! $incident) {
            throw new \RuntimeException('Incident not found.');
        }

        if ($incident->status === 'resolved') {
            throw new \RuntimeException('Incident is resolved and cannot be modified.');
        }

        $nextSeverity = match ($incident->severity) {
            'low' => 'medium',
            'medium' => 'high',
            'high' => 'critical',
            'critical' => null,
            default => null,
        };

        if (! $nextSeverity) {
            throw new \RuntimeException('Incident is already at the highest severity.');
        }

        DB::table('incidents')
            ->where('id', $incidentId)
            ->update([
                'severity' => $nextSeverity,
                'updated_at' => DB::raw('NOW()'),
            ]);

        $updated = $this->findIncident($incidentId);

        $this->logActivity(
            $incidentId,
            $actorId,
            'incident_escalated',
            "Incident {$incident->title} escalated from {$incident->severity_label} to {$updated->severity_label}",
            ['from' => $incident->severity, 'to' => $updated->severity]
        );

        return $updated;
    }

    public function deleteIncident(int $incidentId): void
    {
        // soft-delete: set deleted_at to DB NOW() to preserve history
        DB::table('incidents')->where('id', $incidentId)->update(['deleted_at' => DB::raw('NOW()')]);
    }

    public function logActivity(?int $incidentId, ?int $userId, string $eventType, string $description, array $meta = []): int
    {
        $now = now();

        return (int) DB::table('activity_logs')->insertGetId([
            'incident_id' => $incidentId,
            'user_id' => $userId,
            'event_type' => $eventType,
            'description' => $description,
            'meta' => empty($meta) ? null : json_encode($meta),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function hydrateIncident(object $row): object
    {
        foreach (['due_at', 'last_status_changed_at', 'resolved_at', 'created_at', 'updated_at'] as $field) {
            if (! empty($row->$field)) {
                $row->$field = Carbon::parse($row->$field);
            }
        }

        $row->is_urgent = (bool) $row->is_urgent;
        $row->is_overdue = (bool) $row->is_overdue;

        // due_in_hours might be null if due_at is null
        $row->due_in_hours = isset($row->due_in_hours) ? (int) $row->due_in_hours : null;
        $row->due_category = $row->due_category ?? 'none';
        $row->timeline_state = $this->timelineState($row);
        $row->remaining_time_label = $this->remainingTimeLabel($row);
        $row->timeline_tone = $this->timelineTone($row);

        // mark incomplete incidents (missing key classification fields) to push them to the bottom in UI lists
        $row->incomplete = empty($row->category) || empty($row->severity);
        $row->assignee_name = $row->assignee_name ?? null;

        // badge & color mapping for UI
        if ($row->status === 'resolved') {
            $row->badge = 'RESOLVED';
            $row->badge_color = 'bg-emerald-100 text-emerald-700';
        } elseif ($row->due_category === 'overdue') {
            $row->badge = 'OVERDUE';
            $row->badge_color = 'bg-rose-100 text-rose-700';
        } elseif ($row->due_category === 'due_soon') {
            $row->badge = 'DUE SOON';
            $row->badge_color = 'bg-amber-100 text-amber-700';
        } elseif ($row->due_category === 'deadline_warning') {
            $row->badge = 'DEADLINE';
            $row->badge_color = 'bg-orange-50 text-orange-700';
        } elseif ($row->severity === 'critical') {
            $row->badge = 'CRITICAL';
            $row->badge_color = 'bg-rose-100 text-rose-700';
        } elseif ($row->severity === 'high') {
            $row->badge = 'HIGH';
            $row->badge_color = 'bg-amber-100 text-amber-700';
        } else {
            $row->badge = strtoupper($row->severity ?? 'NORMAL');
            $row->badge_color = $row->severity === 'medium' ? 'bg-slate-200 text-slate-700' : 'bg-indigo-100 text-indigo-700';
        }

        return $row;
    }

    private function timelineState(object $row): string
    {
        if ($row->status === 'resolved') {
            return 'resolved';
        }

        if ($row->due_category === 'overdue') {
            return 'overdue';
        }

        if ($row->due_category === 'due_soon') {
            return 'due_soon';
        }

        return $row->due_category === 'none' ? 'none' : 'normal';
    }

    private function remainingTimeLabel(object $row): string
    {
        if ($row->status === 'resolved') {
            return 'Resolved';
        }

        if (empty($row->due_at)) {
            return 'No due date';
        }

        $hours = (int) ($row->due_in_hours ?? 0);
        $absoluteHours = abs($hours);

        if ($hours < 0) {
            if ($absoluteHours < 24) {
                return "Overdue by {$absoluteHours}h";
            }

            $days = max(1, (int) ceil($absoluteHours / 24));

            return "Overdue by {$days}d";
        }

        if ($absoluteHours < 1) {
            return 'Due now';
        }

        if ($absoluteHours < 24) {
            return "Due in {$absoluteHours}h";
        }

        $days = max(1, (int) ceil($absoluteHours / 24));

        return "Due in {$days}d";
    }

    private function timelineTone(object $row): string
    {
        return match ($this->timelineState($row)) {
            'resolved' => 'green',
            'overdue' => 'red',
            'due_soon' => 'orange',
            default => 'slate',
        };
    }

    private function hydrateActivity(object $row): object
    {
        if (! empty($row->created_at)) {
            $row->created_at = Carbon::parse($row->created_at);
        }

        if (! empty($row->meta) && is_string($row->meta)) {
            $row->meta = json_decode($row->meta, true) ?: [];
        } else {
            $row->meta = $row->meta ? (array) $row->meta : [];
        }

        return $row;
    }
}
