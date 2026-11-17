<?php

namespace App\Http\Controllers;

use App\Models\CrmRegistrationTombstone;
use App\Models\RecruitmentLead;
use App\Rules\Iso8601Timestamp;
use App\Services\CrmRegistrationData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Carbon;

class CrmRegistrationController extends Controller
{
    public function index(Request $request, CrmRegistrationData $data): JsonResponse
    {
        $filters = $request->validate([
            'updated_since' => ['nullable', new Iso8601Timestamp],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string', 'max:2048'],
        ]);
        $cursor = $this->cursor($filters['cursor'] ?? null);

        $startedAt = now()->utc()->format('Y-m-d\TH:i:s\Z');
        $leads = $this->query()
            ->when(isset($filters['updated_since']), fn (Builder $query) => $query
                ->where('updated_at', '>=', Carbon::parse($filters['updated_since'])->setTimezone(config('app.timezone'))))
            ->orderBy('id')
            ->cursorPaginate($filters['per_page'] ?? 50, ['*'], 'cursor', $cursor);

        return response()->json([
            'data' => $leads->getCollection()->map(fn (RecruitmentLead $lead) => $data->registration($lead)),
            'meta' => [
                'schema_version' => config('crm.schema_version'),
                'environment' => config('crm.environment'),
                'next_cursor' => $leads->nextCursor()?->encode(),
                'has_more' => $leads->hasMorePages(),
                'sync_started_at' => $startedAt,
            ],
        ]);
    }

    public function show(string $registration, CrmRegistrationData $data): JsonResponse
    {
        $lead = $this->query()->findOrFail(substr($registration, 4));

        return response()->json(['data' => $data->registration($lead)]);
    }

    public function deleted(Request $request, CrmRegistrationData $data): JsonResponse
    {
        $filters = $request->validate([
            'updated_since' => ['nullable', new Iso8601Timestamp],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string', 'max:2048'],
        ]);
        $startedAt = now()->utc()->format('Y-m-d\TH:i:s\Z');
        $deletions = CrmRegistrationTombstone::query()
            ->when(isset($filters['updated_since']), fn (Builder $query) => $query
                ->where('deleted_at', '>=', Carbon::parse($filters['updated_since'])->setTimezone(config('app.timezone'))))
            ->orderBy('id')->cursorPaginate($filters['per_page'] ?? 50, ['*'], 'cursor', $this->cursor($filters['cursor'] ?? null));

        return response()->json([
            'data' => $deletions->getCollection()->map(fn (CrmRegistrationTombstone $tombstone) => $data->deletion($tombstone)),
            'meta' => [
                'schema_version' => config('crm.schema_version'),
                'environment' => config('crm.environment'),
                'next_cursor' => $deletions->nextCursor()?->encode(),
                'has_more' => $deletions->hasMorePages(),
                'sync_started_at' => $startedAt,
            ],
        ]);
    }

    private function query(): Builder
    {
        return RecruitmentLead::where('source', RecruitmentLead::SOURCE)
            ->with('eventPayments')->withMax('crmWebhookDeliveries', 'id');
    }

    private function cursor(?string $encoded): ?Cursor
    {
        if ($encoded === null) {
            return null;
        }
        $parameters = json_decode(base64_decode(strtr($encoded, '-_', '+/'), true) ?: '', true);
        abort_unless(is_array($parameters) && count($parameters) === 2
            && isset($parameters['id'], $parameters['_pointsToNextItems'])
            && is_bool($parameters['_pointsToNextItems'])
            && (is_int($parameters['id']) || is_string($parameters['id']))
            && preg_match('/^[1-9][0-9]*$/', (string) $parameters['id']), 422, 'Invalid cursor.');

        return new Cursor(['id' => $parameters['id']], $parameters['_pointsToNextItems']);
    }
}
