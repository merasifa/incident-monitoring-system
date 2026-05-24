<?php

namespace App\Http\Controllers;

use App\Enums\IncidentStatus;
use App\Http\Requests\StoreIncidentRequest;
use App\Http\Requests\UpdateIncidentRequest;
use App\Services\IncidentSqlService;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function __construct(
        private readonly IncidentSqlService $sqlService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['severity', 'status', 'category', 'date']);

        // If a query 'q' is provided use the search paginator which supports title/ID/category search
        $q = $request->query('q');
        if (! empty($q)) {
            $incidents = $this->sqlService->searchIncidents($filters, $q, 12);
        } else {
            $incidents = $this->sqlService->paginateIncidents($filters, 12);
        }

        $categories = $this->sqlService->baseQuery()
            ->select('i.category')
            ->distinct()
            ->orderBy('i.category')
            ->pluck('i.category');

        return view('incidents.index', compact('incidents', 'filters', 'categories'));
    }

    public function workflowBoard()
    {
        $board = $this->sqlService->workflowBoard();

        return view('incidents.workflow-board', compact('board'));
    }

    /**
     * JSON endpoint for realtime search used by the frontend.
     */
    public function search(Request $request)
    {
        $q = $request->query('q');
        $filters = $request->only(['severity', 'status', 'category']);

        $perPage = (int) $request->query('per_page', 12);

        $incidents = $this->sqlService->searchIncidents($filters, $q, $perPage);

        return response()->json($incidents->toArray());
    }

    /**
     * Notifications endpoint returning counts and small lists for the UI badge.
     */
    public function notifications(Request $request)
    {
        $userId = $request->user()?->id ?? null;
        $summary = $this->sqlService->notificationSummary($userId);

        return response()->json($summary);
    }

    /**
     * Mark a notification (incident) as read for the current user.
     */
    public function notificationsRead(Request $request)
    {
        $request->validate(['incident_id' => 'required|integer']);
        $incidentId = (int) $request->input('incident_id');

        $incident = $this->sqlService->findIncident($incidentId);
        if (! $incident) {
            return response()->json(['error' => 'Incident not found'], 404);
        }

        // log a lightweight acknowledgement activity (doesn't modify incident)
        $this->sqlService->logActivity($incidentId, $request->user()->id, 'notification_read', "User acknowledged notification for incident {$incident->id}", ['incident_id' => $incidentId]);

        return response()->json(['ok' => true]);
    }

    public function quickAction(Request $request, int $incidentId)
    {
        $request->validate([
            'action' => ['required', 'in:assign_to_me,acknowledge,escalate'],
        ]);

        $actorId = (int) $request->user()->id;

        try {
            $incident = match ($request->string('action')->toString()) {
                'assign_to_me' => $this->sqlService->assignIncident($incidentId, $actorId, $actorId),
                'acknowledge' => $this->sqlService->acknowledgeIncident($incidentId, $actorId),
                'escalate' => $this->sqlService->escalateIncident($incidentId, $actorId),
            };
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Quick action applied to incident {$incident->id}.");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('incidents.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIncidentRequest $request)
    {
        $data = $request->validated();
        $data['last_status_changed_at'] = now();
        $data['resolved_at'] = ($data['status'] ?? null) === IncidentStatus::Resolved->value ? now() : null;

        $incidentId = $this->sqlService->storeIncident($data, $request->user()->id);
        $incident = $this->sqlService->findIncident($incidentId);

        $this->sqlService->logActivity(
            $incidentId,
            $request->user()->id,
            'incident_created',
            "Incident {$incident->title} created",
            ['severity' => $incident->severity, 'status' => $incident->status]
        );

        return redirect()->route('incidents.index')->with('success', 'Incident created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(int $incidentId)
    {
        $incident = $this->sqlService->findIncident($incidentId);
        abort_if(! $incident, 404);

        $activities = $this->sqlService->incidentActivities($incidentId);

        return view('incidents.show', compact('incident', 'activities'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $incidentId)
    {
        $incident = $this->sqlService->findIncident($incidentId);
        abort_if(! $incident, 404);

        // Prevent edit view for resolved incidents — editing is considered finished
        if ($incident->status === IncidentStatus::Resolved->value) {
            return redirect()->route('incidents.show', ['incident' => $incidentId])
                ->with('error', 'Incident is resolved and cannot be edited.');
        }

        return view('incidents.edit', compact('incident'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIncidentRequest $request, int $incidentId)
    {
        // Only status changes are allowed. Preserve other incident fields to keep history integrity.
        $data = $request->validated();
        $incident = $this->sqlService->findIncident($incidentId);
        abort_if(! $incident, 404);

        $oldStatus = $incident->status;

        if ($oldStatus !== $data['status']) {
            $data['last_status_changed_at'] = now();
        }

        // Let service set resolved_at using DB time when transitioning to resolved
        $this->sqlService->updateIncident($incidentId, $data);
        $incident = $this->sqlService->findIncident($incidentId);

        // Log a focused status update activity only
        $this->sqlService->logActivity(
            $incidentId,
            $request->user()->id,
            'status_changed',
            "Status changed from {$oldStatus} to {$incident->status}",
            ['from' => $oldStatus, 'to' => $incident->status]
        );

        if ($oldStatus !== $incident->status && $incident->status === IncidentStatus::Investigating->value) {
            $this->sqlService->logActivity(
                $incidentId,
                $request->user()->id,
                'incident_investigating',
                "Incident {$incident->title} marked as investigating"
            );
        }

        if ($incident->status === IncidentStatus::Resolved->value) {
            $this->sqlService->logActivity(
                $incidentId,
                $request->user()->id,
                'incident_resolved',
                "Incident {$incident->title} resolved"
            );
        }

        return redirect()->route('incidents.index')->with('success', 'Incident status updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, int $incidentId)
    {
        $incident = $this->sqlService->findIncident($incidentId);
        abort_if(! $incident, 404);

        // only admin may delete incidents
        abort_unless($request->user()?->hasRole('admin'), 403);

        $title = $incident->title;
        $this->sqlService->deleteIncident($incidentId);

        $this->sqlService->logActivity(
            null,
            $request->user()?->id,
            'incident_deleted',
            "Incident {$title} deleted"
        );

        return redirect()->route('incidents.index')->with('success', 'Incident deleted (soft) successfully.');
    }
}
