<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
        // - Sales Manager: lihat kontes yg dia buat (all status) + kontes timnya yg status=active (published)
        // - Health Manager: lihat kontes yg dia buat (all status) + kontes yg target HM-nya dia (status=active)
        // - Health Planner: lihat kontes yg dia participant

        $query = Contest::query()->with(['creator', 'creatorRole']);

        if ($user->hasAnyRole(['Admin', 'Head Admin'])) {
            // ✅ lihat semua kontes
        } elseif ($user->hasRole('Sales Manager')) {
            // HM direct child SM
            $hmIds = User::query()
                ->whereIn('id', function ($q) use ($user) {
                    $q->select('child_user_id')
                        ->from('user_hierarchies')
                        ->where('parent_user_id', $user->id);
                })
                ->whereHas('roles', fn($r) => $r->where('name', 'Health Manager'))
                ->pluck('id')
                ->map(fn($v) => (int)$v)
                ->all();

            $query->where(function ($w) use ($user, $hmIds) {
                // ✅ kontes yang dia buat (status apa pun)
                $w->where('created_by_user_id', $user->id);

                // ✅ kontes active/ended yang target HM-nya ada di bawah dia
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
                // ✅ kontes yang dia buat
                $w->where('created_by_user_id', $user->id);

                // ✅ kontes active/ended yang target HM include dia
                $w->orWhere(function ($w2) use ($user) {
                    $w2->whereIn('status', ['active', 'ended'])
                        ->whereJsonContains('rules->target_hm_ids', (int)$user->id);
                });
            });
        } else {
            // ✅ HP / role lain: hanya kontes active/ended yang dia participant
            $query->whereIn('status', ['active', 'ended'])
                ->whereHas('participants', function ($p) use ($user) {
                    $p->where('users.id', $user->id);
                });

            // (opsional) kalau suatu saat HP bisa bikin kontes:
            $query->orWhere('created_by_user_id', $user->id);
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

        // SM: tidak perlu pilih HM (auto downline)
        if ($user->hasRole('Sales Manager')) {
            return view('contests.create');
        }

        // Admin / Head Admin: bisa pilih HM mana saja
        if ($user->hasAnyRole(['Admin', 'Head Admin'])) {
            $healthManagers = User::query()
                ->whereHas('roles', fn($r) => $r->where('name', 'Health Manager'))
                ->orderBy('name')
                ->get();

            return view('contests.create', compact('healthManagers'));
        }

        // HM: pilih HM dalam 1 SM (atau minimal dirinya)
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

            return view('contests.create', compact('healthManagers'));
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
            'target_unit' => ['required', 'integer', 'min:1'],
            'reward' => ['nullable', 'string', 'max:255'],
            'banner' => ['nullable', 'image', 'max:2048'],

            'date_basis' => ['nullable', 'in:install_date,key_in_at'],
            'type' => ['nullable', 'in:leaderboard,qualifier'],

            // qualifier rules
            'monthly_min_personal_ns' => ['nullable', 'integer', 'min:1'],
            'monthly_min_direct_active_partner' => ['nullable', 'integer', 'min:0'],
        ];

        // HM/Admin/Head Admin: wajib pilih HM minimal 1
        if ($user->hasRole('Health Manager') || $user->hasAnyRole(['Admin', 'Head Admin'])) {
            $rules['hm_ids'] = ['required', 'array', 'min:1'];
            $rules['hm_ids.*'] = ['integer'];
        }

        $data = $request->validate($rules);

        return DB::transaction(function () use ($data, $request, $user) {

            $bannerUrl = null;
            if ($request->hasFile('banner')) {
                $path = $request->file('banner')->store('contests', 'public');
                $bannerUrl = $path;
            }

            // Tentukan HM target
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

            // Ambil semua HP di bawah HM terpilih
            $hpIds = $this->getDescendantsByRole($selectedHmIds, 'Health Planner');

            // ✅ Participants = HP ONLY
            $participantIds = array_values(array_unique($hpIds));

            $dateBasis = $data['date_basis'] ?? 'install_date';
            $type = $data['type'] ?? 'leaderboard';

            $isQualifier = ($type === 'qualifier')
                || !empty($data['monthly_min_personal_ns'])
                || !empty($data['monthly_min_direct_active_partner']);

            if ($isQualifier) {
                $type = 'qualifier';
            }

            $baseRules = [];

            // selalu simpan target HM supaya index() HM/SM bisa filter
            $baseRules['target_hm_ids'] = $selectedHmIds;

            if ($isQualifier) {
                $baseRules = array_merge($baseRules, [
                    'monthly_min_personal_ns' => (int) ($data['monthly_min_personal_ns'] ?? 3), // qty personal min
                    'monthly_min_direct_active_partner' => (int) ($data['monthly_min_direct_active_partner'] ?? 3),
                    'direct_partner_active_min_personal_ns' => 1, // partner active minimal qty >= 1
                    'active_partner_definition' => 'partner_personal_qty_min_1',
                    'partner_relation' => 'direct',
                ]);
            }

            $contest = Contest::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'target_unit' => $data['target_unit'],
                'reward' => $data['reward'] ?? null,
                'banner_url' => $bannerUrl,
                'created_by_user_id' => $user->id,
                'created_by_role_id' => $user->roles()->first()?->id,
                'status' => 'draft',

                'date_basis' => $dateBasis,
                'type' => $type,
                'rules' => !empty($baseRules) ? $baseRules : null,
            ]);

            // Eligible roles: HM + HP (boleh tetap, untuk reference)
            $hmRoleId = Role::where('name', 'Health Manager')->value('id');
            $hpRoleId = Role::where('name', 'Health Planner')->value('id');
            $contest->eligibleRoles()->sync(array_values(array_filter([$hmRoleId, $hpRoleId])));

            // Attach participants (pivot)
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

    private function getDirectChildrenIds(int $parentUserId, ?string $roleName = null): array
    {
        $ids = DB::table('user_hierarchies')
            ->where('parent_user_id', $parentUserId)
            ->pluck('child_user_id')
            ->map(fn($v) => (int) $v)
            ->all();

        if (!$roleName) return $ids;

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

        if (empty($rootUserIds)) return [];

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
        if (empty($allDescendantIds)) return [];

        return User::query()
            ->whereIn('id', $allDescendantIds)
            ->whereHas('roles', fn($q) => $q->where('name', $roleName))
            ->pluck('id')
            ->map(fn($v) => (int) $v)
            ->all();
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

        $rows = [];
        $months = [];
        $winners = [];

        $type = $contest->type ?? 'leaderboard';
        $dateBasis = $contest->date_basis ?? 'install_date';
        $rules = (array) ($contest->rules ?? []);
        $isQualifier = ($type === 'qualifier') || !empty($rules);

        if (empty($hpIds)) {
            return view('contests.show', compact('contest', 'rows', 'months', 'winners'));
        }

        $start = $contest->start_date?->copy()->startOfDay();
        $end   = $contest->end_date?->copy()->endOfDay();

        // =========================
        // ✅ MODE: QUALIFIER 133
        // =========================
        if ($isQualifier) {
            $minPersonal = (int) ($rules['monthly_min_personal_ns'] ?? 3);
            $minDirectActive = (int) ($rules['monthly_min_direct_active_partner'] ?? 3);
            $minPartnerActive = (int) ($rules['direct_partner_active_min_personal_ns'] ?? 1);

            $months = $this->buildMonthRanges($start, $end);

            // personal qty per bulan (SUM qty) untuk semua peserta HP
            $personalQtyByMonth = [];
            foreach ($months as $m) {
                $personalQtyByMonth[$m['key']] = DB::table('sales_orders')
                    ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
                    ->whereIn('sales_orders.sales_user_id', $hpIds)
                    ->where('sales_orders.status', 'selesai')
                    ->whereBetween("sales_orders.$dateBasis", [$m['from'], $m['to']])
                    ->groupBy('sales_orders.sales_user_id')
                    ->selectRaw('sales_orders.sales_user_id, COALESCE(SUM(sales_order_items.qty),0) as total_qty')
                    ->pluck('total_qty', 'sales_orders.sales_user_id')
                    ->map(fn($v) => (int) $v)
                    ->all();
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

                // direct partners = direct children role Health Planner
                $directPartnerIds = $this->getDirectChildrenIds((int) $u->id, 'Health Planner');

                foreach ($months as $m) {
                    $monthKey = $m['key'];

                    $personal = (int) (($personalQtyByMonth[$monthKey][$u->id] ?? 0));

                    // active partner: partner dianggap active kalau qty >= minPartnerActive (default 1)
                    $activePartnerCount = 0;
                    if (!empty($directPartnerIds)) {
                        foreach ($directPartnerIds as $pid) {
                            $pPersonal = (int) (($personalQtyByMonth[$monthKey][$pid] ?? 0));
                            if ($pPersonal >= $minPartnerActive) {
                                $activePartnerCount++;
                            }
                        }
                    }

                    $eligibleMonth = ($personal >= $minPersonal) && ($activePartnerCount >= $minDirectActive);
                    if (!$eligibleMonth) $allEligible = false;

                    $perMonth[] = [
                        'key' => $monthKey,
                        'label' => $m['label'],
                        'personal_ns' => $personal, // legacy key untuk blade kamu
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

            return view('contests.show', compact('contest', 'rows', 'months', 'winners'));
        }

        // =========================
        // ✅ MODE: LEADERBOARD
        // =========================
        $progressMap = DB::table('sales_orders')
            ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->whereIn('sales_orders.sales_user_id', $hpIds)
            ->where('sales_orders.status', 'selesai')
            ->when($start && $end, function ($q) use ($start, $end, $dateBasis) {
                $q->whereBetween("sales_orders.$dateBasis", [$start, $end]);
            })
            ->groupBy('sales_orders.sales_user_id')
            ->selectRaw('sales_orders.sales_user_id, COALESCE(SUM(sales_order_items.qty),0) as total_unit')
            ->pluck('total_unit', 'sales_orders.sales_user_id');

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

        // rank with tie
        $rank = 0;
        $prev = null;
        foreach ($rows as $i => $row) {
            if ($prev === null || $row['done'] !== $prev) {
                $rank = $i + 1;
                $prev = $row['done'];
            }
            $rows[$i]['rank'] = $rank;
        }

        return view('contests.show', compact('contest', 'rows', 'months', 'winners'));
    }

    private function buildMonthRanges($start, $end): array
    {
        if (!$start || !$end) return [];

        $cursor = $start->copy()->startOfMonth();
        $last = $end->copy()->startOfMonth();

        $out = [];
        while ($cursor <= $last) {
            $from = $cursor->copy()->startOfMonth()->startOfDay();
            $to = $cursor->copy()->endOfMonth()->endOfDay();

            if ($from < $start) $from = $start->copy();
            if ($to > $end) $to = $end->copy();

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

        // SM: tidak perlu pilih HM (auto)
        if ($user->hasRole('Sales Manager')) {
            return view('contests.edit', compact('contest'));
        }

        // Admin / Head Admin: boleh pilih HM mana saja
        if ($user->hasAnyRole(['Admin', 'Head Admin'])) {
            $healthManagers = User::query()
                ->whereHas('roles', fn($r) => $r->where('name', 'Health Manager'))
                ->orderBy('name')
                ->get();

            // ✅ ambil selected HM dari rules.target_hm_ids (bukan participants)
            $selectedHmIds = (array) (($contest->rules ?? [])['target_hm_ids'] ?? []);

            return view('contests.edit', compact('contest', 'healthManagers', 'selectedHmIds'));
        }

        // HM: pilih HM dalam 1 SM (atau minimal dirinya)
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

            // ✅ ambil selected HM dari rules.target_hm_ids
            $selectedHmIds = (array) (($contest->rules ?? [])['target_hm_ids'] ?? []);

            return view('contests.edit', compact('contest', 'healthManagers', 'selectedHmIds'));
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
            'target_unit' => ['required', 'integer', 'min:1'],
            'reward' => ['nullable', 'string', 'max:255'],
            'banner' => ['nullable', 'image', 'max:2048'],
            'remove_banner' => ['nullable', 'boolean'],

            'date_basis' => ['nullable', 'in:install_date,key_in_at'],
            'type' => ['nullable', 'in:leaderboard,qualifier'],

            'monthly_min_personal_ns' => ['nullable', 'integer', 'min:1'],
            'monthly_min_direct_active_partner' => ['nullable', 'integer', 'min:0'],
        ];

        // HM/Admin/Head Admin: boleh ubah target HM (minimal 1)
        if ($user->hasRole('Health Manager') || $user->hasAnyRole(['Admin', 'Head Admin'])) {
            $rules['hm_ids'] = ['required', 'array', 'min:1'];
            $rules['hm_ids.*'] = ['integer'];
        }

        $data = $request->validate($rules);

        return DB::transaction(function () use ($data, $request, $user, $contest) {

            $bannerUrl = $contest->banner_url;

            if (($data['remove_banner'] ?? false) && $bannerUrl) {
                $bannerUrl = null;
            }

            if ($request->hasFile('banner')) {
                $path = $request->file('banner')->store('contests', 'public');
                $bannerUrl = $path;
            }

            // Tentukan HM target
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

            // Ambil HP di bawah HM target
            $hpIds = $this->getDescendantsByRole($selectedHmIds, 'Health Planner');

            // ✅ Participants = HP ONLY
            $participantIds = array_values(array_unique($hpIds));

            $dateBasis = $data['date_basis'] ?? ($contest->date_basis ?? 'install_date');
            $type = $data['type'] ?? ($contest->type ?? 'leaderboard');

            $isQualifier = ($type === 'qualifier')
                || !empty($data['monthly_min_personal_ns'])
                || !empty($data['monthly_min_direct_active_partner']);

            if ($isQualifier) {
                $type = 'qualifier';
            }

            // merge rules lama supaya target_hm_ids tidak hilang
            $oldRules = (array) ($contest->rules ?? []);
            $newRules = $oldRules;

            // selalu update target HM
            $newRules['target_hm_ids'] = $selectedHmIds;

            if ($isQualifier) {
                $newRules = array_merge($newRules, [
                    'monthly_min_personal_ns' => (int) ($data['monthly_min_personal_ns'] ?? 3),
                    'monthly_min_direct_active_partner' => (int) ($data['monthly_min_direct_active_partner'] ?? 3),
                    'direct_partner_active_min_personal_ns' => 1,
                    'active_partner_definition' => 'partner_personal_qty_min_1',
                    'partner_relation' => 'direct',
                ]);
            } else {
                // kalau bukan qualifier, kamu bisa pilih mau keep rules atau set null
                // tapi kita keep target_hm_ids biar index HM/SM tetap jalan
                $newRules = [
                    'target_hm_ids' => $selectedHmIds,
                ];
            }

            $contest->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'target_unit' => $data['target_unit'],
                'reward' => $data['reward'] ?? null,
                'banner_url' => $bannerUrl,

                'date_basis' => $dateBasis,
                'type' => $type,
                'rules' => !empty($newRules) ? $newRules : null,
            ]);

            // sync participants
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

    public function publish(Contest $contest)
    {
        $this->authorize('update', $contest);

        if ($contest->status !== 'draft') {
            abort(400, 'Kontes sudah dipublish');
        }

        $contest->update(['status' => 'active']);

        return back()->with('success', 'Kontes berhasil dipublish.');
    }
}
