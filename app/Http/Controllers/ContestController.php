<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class ContestController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $query = Contest::query()->with(['creator', 'creatorRole']);

        // RULE (index):
        // - Admin/Head Admin: lihat semua kontes (all status)
        // - Sales Manager: lihat kontes yg dia buat (all status) + kontes timnya yg status=active/ended
        // - Health Manager: lihat kontes yg dia buat (all status) + kontes yg target HM-nya dia (status=active/ended)
        // - Health Planner: lihat kontes active/ended yg dia participant + kontes yg dia buat sendiri (opsional)

        if ($user->hasAnyRole(['Admin', 'Head Admin'])) {
            // lihat semua
        } elseif ($user->hasRole('Sales Manager')) {
            $hmIds = User::query()
                ->whereIn('id', function ($q) use ($user) {
                    $q->select('child_user_id')
                        ->from('user_hierarchies')
                        ->where('parent_user_id', $user->id);
                })
                ->whereHas('roles', fn($r) => $r->where('name', 'Health Manager'))
                ->pluck('id')
                ->map(fn($v) => (int) $v)
                ->all();

            $query->where(function ($w) use ($user, $hmIds) {
                $w->where('created_by_user_id', $user->id);

                $w->orWhere(function ($w2) use ($hmIds) {
                    $w2->whereIn('status', ['active', 'ended']);

                    if (empty($hmIds)) {
                        $w2->whereRaw('1=0');
                        return;
                    }

                    $w2->where(function ($w3) use ($hmIds) {
                        foreach ($hmIds as $hmId) {
                            $w3->orWhereJsonContains('rules->target_hm_ids', $hmId);
                        }
                    });
                });
            });
        } elseif ($user->hasRole('Health Manager')) {
            $query->where(function ($w) use ($user) {
                $w->where('created_by_user_id', $user->id);

                $w->orWhere(function ($w2) use ($user) {
                    $w2->whereIn('status', ['active', 'ended'])
                        ->whereJsonContains('rules->target_hm_ids', (int) $user->id);
                });
            });
        } else {
            $query->where(function ($w) use ($user) {
                $w->where(function ($w2) use ($user) {
                    $w2->whereIn('status', ['active', 'ended'])
                        ->whereHas('participants', function ($p) use ($user) {
                            $p->where('users.id', $user->id);
                        });
                });

                $w->orWhere('created_by_user_id', $user->id);
            });
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $contests = $query->orderByDesc('created_at')->paginate(10);

        return view('contests.index', compact('contests'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        $productOptions = $this->getContestProductOptions();

        if ($user->hasRole('Sales Manager')) {
            return view('contests.create', compact('productOptions'));
        }

        if ($user->hasAnyRole(['Admin', 'Head Admin'])) {
            $healthManagers = User::query()
                ->whereHas('roles', fn($r) => $r->where('name', 'Health Manager'))
                ->orderBy('name')
                ->get();

            return view('contests.create', compact('healthManagers', 'productOptions'));
        }

        if ($user->hasRole('Health Manager')) {
            $parentSmId = DB::table('user_hierarchies')
                ->where('child_user_id', $user->id)
                ->value('parent_user_id');

            $healthManagers = User::query()
                ->whereIn('id', function ($q) use ($parentSmId) {
                    $q->select('child_user_id')
                        ->from('user_hierarchies')
                        ->where('parent_user_id', $parentSmId);
                })
                ->whereHas('roles', fn($r) => $r->where('name', 'Health Manager'))
                ->orderBy('name')
                ->get();

            if (!$healthManagers->contains('id', $user->id)) {
                $healthManagers->prepend($user);
            }

            return view('contests.create', compact('healthManagers', 'productOptions'));
        }

        abort(403);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'max_install_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'target_unit' => [
                'nullable',
                'integer',
                'min:1',
                Rule::requiredIf(fn() => in_array($request->input('product_filter_type'), ['all', 'exclude'], true)),
            ],
            'reward' => ['nullable', 'string', 'max:255'],
            'banner' => ['nullable', 'image', 'max:2048'],

            'type' => ['nullable', 'in:leaderboard,qualifier'],

            'product_filter_type' => ['required', Rule::in(['all', 'specific', 'exclude'])],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['string'],
            'product_min_qtys' => ['nullable', 'array'],
            'product_min_qtys.*' => ['nullable', 'integer', 'min:1'],

            'monthly_min_personal_ns' => [
                'nullable',
                'integer',
                'min:1',
                'exclude_unless:type,qualifier',
                'required_without:monthly_min_direct_active_partner',
            ],
            'monthly_min_direct_active_partner' => [
                'nullable',
                'integer',
                'min:0',
                'exclude_unless:type,qualifier',
                'required_without:monthly_min_personal_ns',
            ],
        ];

        if ($user->hasRole('Health Manager') || $user->hasAnyRole(['Admin', 'Head Admin'])) {
            $rules['hm_ids'] = ['required', 'array', 'min:1'];
            $rules['hm_ids.*'] = ['integer'];
        }

        $data = $request->validate($rules);

        if (
            in_array($data['product_filter_type'] ?? 'all', ['specific', 'exclude'], true)
            && empty($data['product_ids'])
        ) {
            return back()
                ->withErrors(['product_ids' => 'Pilih minimal 1 produk untuk jenis filter ini.'])
                ->withInput();
        }

        return DB::transaction(function () use ($data, $request, $user) {
            $bannerUrl = null;
            if ($request->hasFile('banner')) {
                $path = $request->file('banner')->store('contests', 'public');
                $bannerUrl = $path;
            }

            if ($user->hasRole('Sales Manager')) {
                $selectedHmIds = User::query()
                    ->whereIn('id', function ($q) use ($user) {
                        $q->select('child_user_id')
                            ->from('user_hierarchies')
                            ->where('parent_user_id', $user->id);
                    })
                    ->whereHas('roles', fn($r) => $r->where('name', 'Health Manager'))
                    ->pluck('id')
                    ->map(fn($v) => (int) $v)
                    ->values()
                    ->all();
            } else {
                $selectedHmIds = array_values(array_unique(array_map('intval', $data['hm_ids'] ?? [])));
            }

            $hpIds = $this->getDescendantsByRole($selectedHmIds, 'Health Planner');
            $participantIds = array_values(array_unique($hpIds));

            $type = $data['type'] ?? 'leaderboard';

            $isQualifier = ($type === 'qualifier')
                || !empty($data['monthly_min_personal_ns'])
                || !empty($data['monthly_min_direct_active_partner']);

            if ($isQualifier) {
                $type = 'qualifier';
            }

            $productFilterType = $data['product_filter_type'] ?? 'all';
            $productIds = array_values(array_unique($data['product_ids'] ?? []));
            $productMinQtys = $this->normalizeContestProductMinQtys(
                $productIds,
                $data['product_min_qtys'] ?? []
            );

            $baseRules = [
                'target_hm_ids' => $selectedHmIds,
                'product_filter_type' => $productFilterType,
                'product_ids' => $productIds,
                'product_min_qtys' => $productMinQtys,
            ];

            if ($isQualifier) {
                $qualifierRules = [
                    'monthly_min_personal_ns' => isset($data['monthly_min_personal_ns'])
                        ? (int) $data['monthly_min_personal_ns']
                        : null,

                    'monthly_min_direct_active_partner' => isset($data['monthly_min_direct_active_partner'])
                        ? (int) $data['monthly_min_direct_active_partner']
                        : null,

                    'direct_partner_active_min_personal_ns' => 1,
                    'active_partner_definition' => 'partner_personal_qty_min_1',
                    'partner_relation' => 'direct',
                ];

                $qualifierRules = array_filter($qualifierRules, fn($v) => $v !== null);
                $baseRules = array_merge($baseRules, $qualifierRules);
            }

            $contest = Contest::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'max_install_date' => $data['max_install_date'] ?? null,
                'target_unit' => $data['target_unit'] ?? null,
                'reward' => $data['reward'] ?? null,
                'banner_url' => $bannerUrl,
                'created_by_user_id' => $user->id,
                'created_by_role_id' => $user->roles()->first()?->id,
                'status' => 'draft',
                'type' => $type,
                'rules' => !empty($baseRules) ? $baseRules : null,
            ]);

            $hmRoleId = Role::where('name', 'Health Manager')->value('id');
            $hpRoleId = Role::where('name', 'Health Planner')->value('id');
            $contest->eligibleRoles()->sync(array_values(array_filter([$hmRoleId, $hpRoleId])));

            $now = now();
            $attachData = [];
            foreach ($participantIds as $uid) {
                $attachData[$uid] = [
                    'joined_at' => $now,
                    'status' => 'active',
                ];
            }
            $contest->participants()->sync($attachData);

            return redirect()
                ->route('contests.index')
                ->with('success', 'Kontes berhasil dibuat.');
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Contest $contest)
    {
        if (Gate::denies('view', $contest)) {
            abort(403);
        }

        $hpParticipants = $contest->participants()
            ->whereHas('roles', fn($r) => $r->where('name', 'Health Planner'))
            ->select('users.id', 'users.name')
            ->get();

        $hpIds = $hpParticipants->pluck('id')->all();

        $authUser = request()->user();
        $allParticipantHpIds = $hpIds;

        if ($authUser->hasAnyRole(['Admin', 'Head Admin'])) {
            $visibleHpIds = $allParticipantHpIds;
        } else {
            $downlineHpIds = $this->getDescendantsByRole([(int) $authUser->id], 'Health Planner');
            $candidateIds = array_values(array_unique(array_merge([(int) $authUser->id], $downlineHpIds)));
            $visibleHpIds = array_values(array_intersect($allParticipantHpIds, $candidateIds));
        }

        $hpParticipants = $hpParticipants->whereIn('id', $visibleHpIds)->values();
        $hpIds = $visibleHpIds;

        $rows = [];
        $months = [];
        $winners = [];
        $productOptions = $this->getContestProductOptions();

        $type = $contest->type ?? 'leaderboard';
        $rules = (array) ($contest->rules ?? []);
        $isQualifier = ($type === 'qualifier');

        if (empty($hpIds)) {
            return view('contests.show', compact('contest', 'rows', 'months', 'winners', 'productOptions'));
        }

        $start = $contest->start_date?->copy()->startOfDay();
        $end = $contest->end_date?->copy()->endOfDay();
        $maxInstallDate = ($contest->max_install_date ?? $contest->end_date)?->copy()->endOfDay();

        if ($isQualifier) {
            $minPersonal = isset($rules['monthly_min_personal_ns'])
                ? (int) $rules['monthly_min_personal_ns']
                : null;

            $minDirectActive = isset($rules['monthly_min_direct_active_partner'])
                ? (int) $rules['monthly_min_direct_active_partner']
                : null;

            $minPartnerActive = (int) ($rules['direct_partner_active_min_personal_ns'] ?? 1);

            $months = $this->buildMonthRanges($start, $end);

            $personalQtyByMonth = [];
            foreach ($months as $m) {
                $personalQtyByMonth[$m['key']] = $this->buildContestUserQtyMap(
                    $hpIds,
                    $m['from'],
                    $m['to'],
                    $maxInstallDate,
                    $rules
                );
            }

            $rows = $hpParticipants->map(function ($u) use (
                $months,
                $personalQtyByMonth,
                $minPersonal,
                $minDirectActive,
                $minPartnerActive
            ) {
                $perMonth = [];
                $allEligible = true;

                $directPartnerIds = $this->getDirectChildrenIds((int) $u->id, 'Health Planner');

                foreach ($months as $m) {
                    $monthKey = $m['key'];
                    $personal = (int) (($personalQtyByMonth[$monthKey][$u->id] ?? 0));

                    $activePartnerCount = 0;
                    if (!empty($directPartnerIds)) {
                        foreach ($directPartnerIds as $pid) {
                            $pPersonal = (int) (($personalQtyByMonth[$monthKey][$pid] ?? 0));
                            if ($pPersonal >= $minPartnerActive) {
                                $activePartnerCount++;
                            }
                        }
                    }

                    $passPersonal = ($minPersonal === null) ? true : ($personal >= $minPersonal);
                    $passPartner = ($minDirectActive === null) ? true : ($activePartnerCount >= $minDirectActive);

                    $eligibleMonth = $passPersonal && $passPartner;
                    if (!$eligibleMonth) {
                        $allEligible = false;
                    }

                    $perMonth[] = [
                        'key' => $monthKey,
                        'label' => $m['label'],
                        'personal_ns' => $personal,
                        'active_partner' => $activePartnerCount,
                        'eligible' => $eligibleMonth,
                    ];
                }

                return [
                    'user_id' => (int) $u->id,
                    'name' => $u->name,
                    'months' => $perMonth,
                    'is_winner' => $allEligible,
                ];
            })->values()->all();

            $winners = array_values(array_filter($rows, fn($r) => $r['is_winner'] === true));

            return view('contests.show', compact('contest', 'rows', 'months', 'winners', 'productOptions'));
        }

        $progressMap = $this->buildContestUserQtyMap(
            $hpIds,
            $start,
            $end,
            $maxInstallDate,
            $rules
        );

        $target = (int) ($contest->target_unit ?? 0);

        $rows = $hpParticipants->map(function ($u) use ($progressMap, $target) {
            $done = (int) ($progressMap[$u->id] ?? 0);
            $pct = $target > 0 ? min(100, (int) round(($done / $target) * 100)) : 0;

            return [
                'user_id' => (int) $u->id,
                'name' => $u->name,
                'done' => $done,
                'pct' => $pct,
            ];
        })->sortByDesc('done')->values()->all();

        $rank = 0;
        $prev = null;
        foreach ($rows as $i => $row) {
            if ($prev === null || $row['done'] !== $prev) {
                $rank = $i + 1;
                $prev = $row['done'];
            }
            $rows[$i]['rank'] = $rank;
        }

        $productOptions = $this->getContestProductOptions();

        return view('contests.show', compact('contest', 'rows', 'months', 'winners', 'productOptions'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Contest $contest)
    {
        if (Gate::denies('update', $contest)) {
            abort(403);
        }

        if (in_array($contest->status, ['active', 'ended'], true)) {
            abort(403, 'Kontes yang sudah aktif / selesai tidak bisa diubah.');
        }

        $user = $request->user();
        $productOptions = $this->getContestProductOptions();

        if ($user->hasRole('Sales Manager')) {
            return view('contests.edit', compact('contest', 'productOptions'));
        }

        if ($user->hasAnyRole(['Admin', 'Head Admin'])) {
            $healthManagers = User::query()
                ->whereHas('roles', fn($r) => $r->where('name', 'Health Manager'))
                ->orderBy('name')
                ->get();

            $selectedHmIds = (array) (($contest->rules ?? [])['target_hm_ids'] ?? []);

            return view('contests.edit', compact('contest', 'healthManagers', 'selectedHmIds', 'productOptions'));
        }

        if ($user->hasRole('Health Manager')) {
            $parentSmId = DB::table('user_hierarchies')
                ->where('child_user_id', $user->id)
                ->value('parent_user_id');

            $healthManagers = User::query()
                ->whereIn('id', function ($q) use ($parentSmId) {
                    $q->select('child_user_id')
                        ->from('user_hierarchies')
                        ->where('parent_user_id', $parentSmId);
                })
                ->whereHas('roles', fn($r) => $r->where('name', 'Health Manager'))
                ->orderBy('name')
                ->get();

            if (!$healthManagers->contains('id', $user->id)) {
                $healthManagers->prepend($user);
            }

            $selectedHmIds = (array) (($contest->rules ?? [])['target_hm_ids'] ?? []);

            return view('contests.edit', compact('contest', 'healthManagers', 'selectedHmIds', 'productOptions'));
        }

        abort(403);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contest $contest)
    {
        if (Gate::denies('update', $contest)) {
            abort(403);
        }

        if (in_array($contest->status, ['active', 'ended'], true)) {
            abort(403, 'Kontes yang sudah aktif / selesai tidak bisa diubah.');
        }

        $user = $request->user();

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'max_install_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'target_unit' => [
                'nullable',
                'integer',
                'min:1',
                Rule::requiredIf(fn() => in_array($request->input('product_filter_type'), ['all', 'exclude'], true)),
            ],
            'reward' => ['nullable', 'string', 'max:255'],
            'banner' => ['nullable', 'image', 'max:2048'],
            'remove_banner' => ['nullable', 'boolean'],

            'type' => ['nullable', 'in:leaderboard,qualifier'],

            'product_filter_type' => ['required', Rule::in(['all', 'specific', 'exclude'])],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['string'],
            'product_min_qtys' => ['nullable', 'array'],
            'product_min_qtys.*' => ['nullable', 'integer', 'min:1'],

            'monthly_min_personal_ns' => [
                'nullable',
                'integer',
                'min:1',
                'exclude_unless:type,qualifier',
                'required_without:monthly_min_direct_active_partner',
            ],
            'monthly_min_direct_active_partner' => [
                'nullable',
                'integer',
                'min:0',
                'exclude_unless:type,qualifier',
                'required_without:monthly_min_personal_ns',
            ],
        ];

        if ($user->hasRole('Health Manager') || $user->hasAnyRole(['Admin', 'Head Admin'])) {
            $rules['hm_ids'] = ['required', 'array', 'min:1'];
            $rules['hm_ids.*'] = ['integer'];
        }

        $data = $request->validate($rules);

        if (
            in_array($data['product_filter_type'] ?? 'all', ['specific', 'exclude'], true)
            && empty($data['product_ids'])
        ) {
            return back()
                ->withErrors(['product_ids' => 'Pilih minimal 1 produk untuk jenis filter ini.'])
                ->withInput();
        }

        return DB::transaction(function () use ($data, $request, $user, $contest) {
            $bannerUrl = $contest->banner_url;

            if (($data['remove_banner'] ?? false) && $bannerUrl) {
                $bannerUrl = null;
            }

            if ($request->hasFile('banner')) {
                $path = $request->file('banner')->store('contests', 'public');
                $bannerUrl = $path;
            }

            if ($user->hasRole('Sales Manager')) {
                $selectedHmIds = User::query()
                    ->whereIn('id', function ($q) use ($user) {
                        $q->select('child_user_id')
                            ->from('user_hierarchies')
                            ->where('parent_user_id', $user->id);
                    })
                    ->whereHas('roles', fn($r) => $r->where('name', 'Health Manager'))
                    ->pluck('id')
                    ->map(fn($v) => (int) $v)
                    ->values()
                    ->all();
            } else {
                $selectedHmIds = array_values(array_unique(array_map('intval', $data['hm_ids'] ?? [])));
            }

            $hpIds = $this->getDescendantsByRole($selectedHmIds, 'Health Planner');
            $participantIds = array_values(array_unique($hpIds));

            $type = $data['type'] ?? ($contest->type ?? 'leaderboard');

            $isQualifier = ($type === 'qualifier')
                || !empty($data['monthly_min_personal_ns'])
                || !empty($data['monthly_min_direct_active_partner']);

            if ($isQualifier) {
                $type = 'qualifier';
            }

            $productFilterType = $data['product_filter_type'] ?? 'all';
            $productIds = array_values(array_unique($data['product_ids'] ?? []));
            $productMinQtys = $this->normalizeContestProductMinQtys(
                $productIds,
                $data['product_min_qtys'] ?? []
            );

            $oldRules = (array) ($contest->rules ?? []);
            $newRules = $oldRules;

            $newRules['target_hm_ids'] = $selectedHmIds;
            $newRules['product_filter_type'] = $productFilterType;
            $newRules['product_ids'] = $productIds;
            $newRules['product_min_qtys'] = $productMinQtys;

            if ($isQualifier) {
                $qualifierRules = [
                    'monthly_min_personal_ns' => isset($data['monthly_min_personal_ns'])
                        ? (int) $data['monthly_min_personal_ns']
                        : null,

                    'monthly_min_direct_active_partner' => isset($data['monthly_min_direct_active_partner'])
                        ? (int) $data['monthly_min_direct_active_partner']
                        : null,

                    'direct_partner_active_min_personal_ns' => 1,
                    'active_partner_definition' => 'partner_personal_qty_min_1',
                    'partner_relation' => 'direct',
                ];

                $qualifierRules = array_filter($qualifierRules, fn($v) => $v !== null);
                $newRules = array_merge($newRules, $qualifierRules);
            } else {
                unset(
                    $newRules['monthly_min_personal_ns'],
                    $newRules['monthly_min_direct_active_partner'],
                    $newRules['direct_partner_active_min_personal_ns'],
                    $newRules['active_partner_definition'],
                    $newRules['partner_relation']
                );
            }

            $contest->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'max_install_date' => $data['max_install_date'] ?? null,
                'target_unit' => $data['target_unit'] ?? null,
                'reward' => $data['reward'] ?? null,
                'banner_url' => $bannerUrl,
                'type' => $type,
                'rules' => !empty($newRules) ? $newRules : null,
            ]);

            $now = now();
            $attachData = [];
            foreach ($participantIds as $uid) {
                $attachData[$uid] = [
                    'joined_at' => $now,
                    'status' => 'active',
                ];
            }
            $contest->participants()->sync($attachData);

            return redirect()
                ->route('contests.show', $contest)
                ->with('success', 'Kontes berhasil diperbarui.');
        });
    }

    public function destroy(Contest $contest)
    {
        //
    }

    private function canUnpublish(Request $request, Contest $contest): bool
    {
        $user = $request->user();

        return $user->hasAnyRole(['Admin', 'Head Admin'])
            || (int) $contest->created_by_user_id === (int) $user->id;
    }

    public function publish(Contest $contest)
    {
        $this->authorize('update', $contest);

        if ($contest->status !== 'draft') {
            abort(400, 'Kontes sudah dipublish');
        }

        $contest->update(['status' => 'active']);

        return back()->with('success', 'Kontes berhasil dipublish.');
    }

    public function unpublish(Request $request, Contest $contest)
    {
        if (!$this->canUnpublish($request, $contest)) {
            abort(403);
        }

        if ($contest->status !== 'active') {
            abort(400, 'Hanya kontes aktif yang bisa di-unpublish.');
        }

        $contest->update(['status' => 'draft']);

        return back()->with('success', 'Kontes berhasil di-unpublish.');
    }

    private function getDirectChildrenIds(int $parentUserId, ?string $roleName = null): array
    {
        $ids = DB::table('user_hierarchies')
            ->where('parent_user_id', $parentUserId)
            ->pluck('child_user_id')
            ->map(fn($v) => (int) $v)
            ->all();

        if (!$roleName) {
            return $ids;
        }

        return User::query()
            ->whereIn('id', $ids ?: [0])
            ->whereHas('roles', fn($q) => $q->where('name', $roleName))
            ->pluck('id')
            ->map(fn($v) => (int) $v)
            ->all();
    }

    private function getDescendantsByRole(array $rootUserIds, string $roleName): array
    {
        $rootUserIds = array_values(array_unique(array_filter($rootUserIds)));

        if (empty($rootUserIds)) {
            return [];
        }

        $visited = [];
        $queue = $rootUserIds;

        while (!empty($queue)) {
            $batch = $queue;
            $queue = [];

            $children = DB::table('user_hierarchies')
                ->whereIn('parent_user_id', $batch)
                ->pluck('child_user_id')
                ->map(fn($v) => (int) $v)
                ->all();

            foreach ($children as $cid) {
                if (!isset($visited[$cid])) {
                    $visited[$cid] = true;
                    $queue[] = $cid;
                }
            }
        }

        $allDescendantIds = array_keys($visited);
        if (empty($allDescendantIds)) {
            return [];
        }

        return User::query()
            ->whereIn('id', $allDescendantIds)
            ->whereHas('roles', fn($q) => $q->where('name', $roleName))
            ->pluck('id')
            ->map(fn($v) => (int) $v)
            ->all();
    }

    private function buildMonthRanges($start, $end): array
    {
        if (!$start || !$end) {
            return [];
        }

        $cursor = $start->copy()->startOfMonth();
        $last = $end->copy()->startOfMonth();

        $out = [];
        while ($cursor <= $last) {
            $from = $cursor->copy()->startOfMonth()->startOfDay();
            $to = $cursor->copy()->endOfMonth()->endOfDay();

            if ($from < $start) {
                $from = $start->copy();
            }

            if ($to > $end) {
                $to = $end->copy();
            }

            $out[] = [
                'key' => $cursor->format('Y-m'),
                'label' => $cursor->format('M Y'),
                'from' => $from,
                'to' => $to,
            ];

            $cursor->addMonthNoOverflow();
        }

        return $out;
    }

    private function getContestProductOptions()
    {
        return DB::table('products')
            ->select('id', 'product_name', 'type')
            ->where('is_active', 1)
            ->orderBy('product_name')
            ->get()
            ->map(fn($item) => (object) [
                'value' => 'product:' . $item->id,
                'label' => $item->type === 'bundle'
                    ? '[Bundle] ' . $item->product_name
                    : $item->product_name,
                'type' => $item->type === 'bundle' ? 'bundle' : 'product',
            ])
            ->values();
    }

    private function normalizeContestProductIds(array $productIds): array
    {
        $ids = [];

        foreach ($productIds as $value) {
            if (str_starts_with($value, 'product:')) {
                $ids[] = (int) str_replace('product:', '', $value);
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function normalizeContestProductMinQtys(array $productIds, array $productMinQtys = []): array
    {
        $normalized = [];

        foreach ($productIds as $value) {
            $minQty = (int) ($productMinQtys[$value] ?? 1);
            $normalized[$value] = max($minQty, 1);
        }

        return $normalized;
    }

    private function applyContestProductFilter(Builder $query, array $rules): void
    {
        $filterType = $rules['product_filter_type'] ?? 'all';
        $rawIds = (array) ($rules['product_ids'] ?? []);
        $productIds = $this->normalizeContestProductIds($rawIds);

        if ($filterType === 'all' || empty($productIds)) {
            return;
        }

        if ($filterType === 'specific') {
            $query->whereIn('sales_order_items.product_id', $productIds);
            return;
        }

        if ($filterType === 'exclude') {
            $query->where(function ($q) use ($productIds) {
                $q->whereNull('sales_order_items.product_id')
                    ->orWhereNotIn('sales_order_items.product_id', $productIds);
            });
        }
    }

    private function buildContestUserQtyMap(array $hpIds, $from, $to, $maxInstallDate, array $rules): array
    {
        if (empty($hpIds)) {
            return [];
        }

        $filterType = $rules['product_filter_type'] ?? 'all';
        $rawIds = (array) ($rules['product_ids'] ?? []);
        $productIds = $this->normalizeContestProductIds($rawIds);
        $productMinQtysRaw = (array) ($rules['product_min_qtys'] ?? []);
        $productMinQtys = $this->normalizeContestProductMinQtys($rawIds, $productMinQtysRaw);

        // total isi bundle per bundle_id
        $bundleQtySubquery = DB::table('bundle_items')
            ->selectRaw('bundle_id, COALESCE(SUM(qty), 0) as bundle_total_qty')
            ->groupBy('bundle_id');

        $baseQuery = DB::table('sales_orders')
            ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->join('products', 'products.id', '=', 'sales_order_items.product_id')
            ->leftJoinSub($bundleQtySubquery, 'bundle_qty_map', function ($join) {
                $join->on('bundle_qty_map.bundle_id', '=', 'sales_order_items.product_id');
            })
            ->whereIn('sales_orders.sales_user_id', $hpIds)
            ->where('sales_orders.status', 'selesai')
            ->whereBetween('sales_orders.key_in_at', [$from, $to])
            ->whereNotNull('sales_orders.install_date')
            ->where('sales_orders.install_date', '<=', $maxInstallDate);

        // filter produk untuk specific / exclude
        $this->applyContestProductFilter($baseQuery, $rules);

        // qty efektif:
        // - produk biasa => sales_order_items.qty
        // - bundle => sales_order_items.qty * total isi bundle
        $effectiveQtyExpr = "
        CASE
            WHEN products.type = 'bundle'
                THEN sales_order_items.qty * COALESCE(NULLIF(bundle_qty_map.bundle_total_qty, 0), 1)
            ELSE sales_order_items.qty
        END
    ";

        // kalau bukan specific, atau tidak ada min qty produk, cukup jumlahkan qty efektif
        if ($filterType !== 'specific' || empty($productIds) || empty($productMinQtys)) {
            return $baseQuery
                ->groupBy('sales_orders.sales_user_id')
                ->selectRaw("
                sales_orders.sales_user_id,
                COALESCE(SUM({$effectiveQtyExpr}), 0) as total_qty
            ")
                ->pluck('total_qty', 'sales_orders.sales_user_id')
                ->map(fn($v) => (int) $v)
                ->all();
        }

        // untuk specific + min qty, hitung per user per product dulu
        $perProductRows = (clone $baseQuery)
            ->groupBy('sales_orders.sales_user_id', 'sales_order_items.product_id')
            ->selectRaw("
            sales_orders.sales_user_id,
            sales_order_items.product_id,
            COALESCE(SUM({$effectiveQtyExpr}), 0) as total_qty
        ")
            ->get();

        $userProductQtyMap = [];
        foreach ($perProductRows as $row) {
            $userId = (int) $row->sales_user_id;
            $productId = (int) $row->product_id;
            $userProductQtyMap[$userId][$productId] = (int) $row->total_qty;
        }

        $result = [];
        foreach ($hpIds as $userId) {
            $userId = (int) $userId;
            $sum = 0;

            foreach ($productIds as $productId) {
                $rawKey = 'product:' . $productId;
                $qty = (int) ($userProductQtyMap[$userId][$productId] ?? 0);
                $minQty = (int) ($productMinQtys[$rawKey] ?? 1);

                if ($qty >= $minQty) {
                    $sum += $qty;
                }
            }

            $result[$userId] = $sum;
        }

        return $result;
    }
}
