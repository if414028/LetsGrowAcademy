<?php

namespace App\Http\Controllers;

use App\Models\RecruitmentLead;
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
            'updated_since' => ['nullable', 'date_format:Y-m-d\TH:i:sP'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string', 'max:2048'],
        ]);
        $cursor = $this->cursor($filters['cursor'] ?? null);

        $startedAt = now()->toIso8601String();
        $leads = $this->query()
            ->when(isset($filters['updated_since']), fn (Builder $query) => $query
                ->where('updated_at', '>=', Carbon::parse($filters['updated_since'])->setTimezone(config('app.timezone'))))
            ->orderBy('id')
            ->cursorPaginate($filters['per_page'] ?? 50, ['*'], 'cursor', $cursor);

        return response()->json([
            'data' => $leads->getCollection()->map(fn (RecruitmentLead $lead) => $data->registration($lead)),
            'meta' => [
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
