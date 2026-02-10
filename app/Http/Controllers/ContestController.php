<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Gate;

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

        // ✅ SM: lihat kontes untuk timnya (HM downline + HP di bawahnya)
        if ($user->hasRole('Sales Manager')) {
            // ambil semua HM direct child dari SM
            $hmIds = User::query()
                ->whereIn('id', function ($q) use ($user) {
                    $q->select('child_user_id')
                        ->from('user_hierarchies')
                        ->where('parent_user_id', $user->id);
                })
                ->whereHas('roles', fn($r) => $r->where('name', 'Health Manager'))
                ->pluck('id')
                ->all();

            // jika SM belum punya HM, biar ga error / ga return semua data
            $query->whereHas('participants', function ($p) use ($hmIds) {
                $p->whereIn('users.id', $hmIds ?: [0]);
            });
        } else {
            // ✅ selain SM: kontes yang user ini terdaftar
            $query->whereHas('participants', function ($p) use ($user) {
                $p->where('users.id', $user->id);
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
                // auto: semua HM direct child SM
                $selectedHmIds = User::query()
                    ->whereIn('id', function ($q) use ($user) {
                        $q->select('child_user_id')
                            ->from('user_hierarchies')
                            ->where('parent_user_id', $user->id);
                    })
                    ->whereHas('roles', fn($r) => $r->where('name', 'Health Manager'))
                    ->pluck('id')
                    ->values()
                    ->all();
            } else {
                // HM/Admin/Head Admin: dari input
                $selectedHmIds = array_values(array_unique(array_map('intval', $data['hm_ids'] ?? [])));
            }

            // Ambil semua HP di bawah HM terpilih
            $hpIds = $this->getDescendantsByRole($selectedHmIds, 'Health Planner');

            // Participants = HM + HP + creator
            $participantIds = array_values(array_unique(array_merge($selectedHmIds, $hpIds, [$user->id])));

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
            ]);

            // Eligible roles: HM + HP
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

        // filter by role
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
        // authorize view (pakai Gate biar aman walau authorize() belum kepakai)
        if (Gate::denies('view', $contest)) {
            abort(403);
        }

        // ambil peserta HP saja (Health Planner)
        $hpParticipants = $contest->participants()
            ->whereHas('roles', fn($r) => $r->where('name', 'Health Planner'))
            ->select('users.id', 'users.name')
            ->get();

        $hpIds = $hpParticipants->pluck('id')->all();

        // kalau belum ada HP, leaderboard kosong
        $rows = [];
        if (!empty($hpIds)) {
            $start = $contest->start_date?->startOfDay();
            $end   = $contest->end_date?->endOfDay();

            // progress = count sales order selesai dalam periode
            $progressMap = DB::table('sales_orders')
                ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
                ->whereIn('sales_orders.sales_user_id', $hpIds)
                ->where('sales_orders.status', 'selesai')
                ->when($start && $end, function ($q) use ($start, $end) {
                    $q->whereBetween('sales_orders.install_date', [$start, $end]); // atau key_in_at
                })
                ->groupBy('sales_orders.sales_user_id')
                ->selectRaw('sales_orders.sales_user_id, COALESCE(SUM(sales_order_items.qty),0) as total_unit')
                ->pluck('total_unit', 'sales_orders.sales_user_id');

            $target = (int) ($contest->target_unit ?? 0);

            $rows = $hpParticipants->map(function ($u) use ($progressMap, $target) {
                $done = (int) ($progressMap[$u->id] ?? 0);
                $pct = $target > 0 ? min(100, (int) round(($done / $target) * 100)) : 0;

                return [
                    'user_id' => $u->id,
                    'name' => $u->name,
                    'done' => $done,
                    'pct' => $pct,
                ];
            })->sortByDesc('done')->values()->all();

            // assign rank (handle tie)
            $rank = 0;
            $prev = null;
            foreach ($rows as $i => $row) {
                if ($prev === null || $row['done'] !== $prev) {
                    $rank = $i + 1;
                    $prev = $row['done'];
                }
                $rows[$i]['rank'] = $rank;
            }
        }

        return view('contests.show', compact('contest', 'rows'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Contest $contest)
    {
        if (Gate::denies('update', $contest)) {
            abort(403);
        }

        // hard lock (jaga-jaga)
        if (in_array($contest->status, ['active', 'ended'], true)) {
            abort(403, 'Kontes yang sudah aktif / selesai tidak bisa diubah.');
        }

        $user = $request->user();
        $healthManagers = collect();

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

            // HM yang sudah terdaftar di kontes ini
            $selectedHmIds = $contest->participants()
                ->whereHas('roles', fn($r) => $r->where('name', 'Health Manager'))
                ->pluck('users.id')
                ->map(fn($v) => (int)$v)
                ->all();

            return view('contests.edit', compact('contest', 'healthManagers', 'selectedHmIds'));
        }

        // HM: pilih HM dalam 1 SM (atau minimal dirinya)
        if ($user->hasRole('Health Manager')) {
            $parentSmId = DB::table('user_hierarchy')
                ->where('child_user_id', $user->id)
                ->value('parent_user_id');

            $healthManagers = User::query()
                ->whereIn('id', function ($q) use ($parentSmId) {
                    $q->select('child_user_id')
                        ->from('user_hierarchy')
                        ->where('parent_user_id', $parentSmId);
                })
                ->whereHas('roles', fn($r) => $r->where('name', 'Health Manager'))
                ->orderBy('name')
                ->get();

            if (!$healthManagers->contains('id', $user->id)) {
                $healthManagers->prepend($user);
            }

            $selectedHmIds = $contest->participants()
                ->whereHas('roles', fn($r) => $r->where('name', 'Health Manager'))
                ->pluck('users.id')
                ->map(fn($v) => (int)$v)
                ->all();

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
        ];

        // HM/Admin/Head Admin: boleh ubah peserta HM (minimal 1)
        if ($user->hasRole('Health Manager') || $user->hasAnyRole(['Admin', 'Head Admin'])) {
            $rules['hm_ids'] = ['required', 'array', 'min:1'];
            $rules['hm_ids.*'] = ['integer'];
        }

        $data = $request->validate($rules);

        return DB::transaction(function () use ($data, $request, $user, $contest) {

            // handle banner
            $bannerUrl = $contest->banner_url;

            if (($data['remove_banner'] ?? false) && $bannerUrl) {
                // optional: hapus file juga kalau mau
                // Storage::disk('public')->delete($bannerUrl);
                $bannerUrl = null;
            }

            if ($request->hasFile('banner')) {
                $path = $request->file('banner')->store('contests', 'public');
                $bannerUrl = $path;
            }

            // Tentukan HM target (untuk update peserta)
            if ($user->hasRole('Sales Manager')) {
                $selectedHmIds = User::query()
                    ->whereIn('id', function ($q) use ($user) {
                        $q->select('child_user_id')
                            ->from('user_hierarchy')
                            ->where('parent_user_id', $user->id);
                    })
                    ->whereHas('roles', fn($r) => $r->where('name', 'Health Manager'))
                    ->pluck('id')
                    ->values()
                    ->all();
            } else {
                $selectedHmIds = array_values(array_unique(array_map('intval', $data['hm_ids'] ?? [])));
            }

            // Ambil semua HP di bawah HM terpilih
            $hpIds = $this->getDescendantsByRole($selectedHmIds, 'Health Planner');

            $participantIds = array_values(array_unique(array_merge($selectedHmIds, $hpIds, [$contest->created_by_user_id])));

            // update contest
            $contest->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'target_unit' => $data['target_unit'],
                'reward' => $data['reward'] ?? null,
                'banner_url' => $bannerUrl,
            ]);

            // update participants (sync)
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


    /**
     * Remove the specified resource from storage.
     */
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
