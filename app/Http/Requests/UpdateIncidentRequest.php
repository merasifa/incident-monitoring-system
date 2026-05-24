<?php

namespace App\Http\Requests;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class UpdateIncidentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // ensure incident exists and is not resolved (resolved incidents are final and not editable)
        $incidentId = $this->route('incident');
        if (! $incidentId) {
            return false;
        }

        $user = $this->user();
        if (! $user || ! $user->hasRole(['admin', 'operator'])) {
            return false;
        }

        $current = DB::table('incidents')->where('id', $incidentId)->value('status');
        if ($current === null) {
            return false;
        }

        // disallow any update if already resolved
        return $current !== 'resolved';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Determine allowed transitions based on current status
        $incidentId = $this->route('incident');
        $current = $incidentId ? DB::table('incidents')->where('id', $incidentId)->value('status') : null;

        $allowed = match ($current) {
            'open' => ['open', 'investigating', 'resolved'],
            'investigating' => ['investigating', 'resolved'],
            default => IncidentStatus::values(),
        };

        return [
            'status' => ['required', 'in:' . implode(',', $allowed)],
        ];
    }
}
