<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserHierarchy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Models\SalesOrder;
use App\Models\PerformanceCutoff;
use Carbon\Carbon;

class UserController extends Controller
{
    private function isAdminLevel(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Head Admin']);
    }

    private function denyIfTargetIsHeadAdmin(User $target, User $actor): void
    {
        // Admin tidak boleh edit/lihat Head Admin
        if ($target->hasRole('Head Admin') && !$actor->hasRole('Head Admin')) {
            abort(403, 'Kamu tidak punya akses untuk mengubah akun Head Admin.');
        }
    }


    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $users = User::query()
            ->with('roles')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%");
            })
            ->latest()
            ->paginate(10)
            ->appends(['q' => $q]);

        return view('users.index', compact('users', 'q'));
    }

    public function show(User $user)
    {
        $authUser = request()->user();

        // Admin/Head Admin: bebas (dengan rule Head Admin tetap)
        if (!$this->isAdminLevel($authUser)) {
            // Non-admin boleh lihat diri sendiri atau downliner
            $allowed = $this->isDownliner($authUser->id, $user->id);

            if (!$allowed) {
                abort(403, 'Kamu hanya bisa melihat profil diri sendiri atau downliner kamu.');
            }
        }


        // Admin tidak boleh lihat Head Admin
        $this->denyIfTargetIsHeadAdmin($user, $authUser);

        // Roles
        $user->load('roles');

        // Parent (referrer)
        $parentHierarchy = \App\Models\UserHierarchy::with('parentUser')
            ->where('child_user_id', $user->id)
            ->first();

        $parentUser = $parentHierarchy?->parentUser;

        $hmUser = null;
        $hmUser = $this->findHealthManagerForUser($user->id);

        // Direct reports (bawahan langsung)
        $childHierarchies = \App\Models\UserHierarchy::with('childUser.roles')
            ->where('parent_user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        $directReports = $childHierarchies
            ->map(fn($h) => $h->childUser)
            ->filter();

        $childrenCount = $directReports->count();

        [$from, $to, $isManual] = $this->normalizeDateRange(
            request()->get('from'),
            request()->get('to')
        );

        $cutoff = PerformanceCutoff::current();

        $defaultFrom = $cutoff?->start_date ?? Carbon::now()->startOfMonth()->subMonth()->toDateString();
        $defaultTo   = $cutoff?->end_date   ?? Carbon::now()->endOfMonth()->addMonth()->toDateString();

        if (!$isManual) {
            $from = $defaultFrom;
            $to   = $defaultTo;
        }

        $downlineTree = $this->buildDownlineTree($user->id, $from, $to, $isManual);

        return view('users.show', compact(
            'user',
            'parentUser',
            'childrenCount',
            'directReports',
            'downlineTree',
            'hmUser'
        ));
    }

    public function create()
    {
        $authUser = request()->user();

        $roles = Role::query()
            ->orderBy('name')
            ->when(!$authUser->hasRole('Head Admin'), function ($q) {
                $q->where('name', '!=', 'Head Admin');
            })
            ->get();

        $roleRanks = config('roles.rank');

        $oldReferrer = null;
        if (old('referrer_user_id')) {
            $oldReferrer = User::with('roles')->find(old('referrer_user_id'));
        }

        return view('users.create', compact('roles', 'roleRanks', 'oldReferrer'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // basic account
            'name' => ['required', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // hierarchy & role
            'role' => ['required', 'string', 'exists:roles,name'],
            'referrer_user_id' => ['required', 'exists:users,id'],

            // ERD fields
            'status' => ['nullable', 'string', 'max:50'],
            'dst_code' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'join_date' => ['nullable', 'date'],
            'city_of_domicile' => ['nullable', 'string', 'max:255'],

            // uploads
            'photo' => ['nullable', 'image', 'max:2048'], // 2MB
            'id_card' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'], // 4MB
        ]);

        // ambil referrer + role
        $referrer = User::with('roles')->findOrFail($validated['referrer_user_id']);

        $rankMap = config('roles.rank');
        $refRole = $referrer->getRoleNames()->first();
        $newRole = $validated['role'];

        $authUser = $request->user();

        if ($newRole === 'Head Admin' && !$authUser->hasRole('Head Admin')) {
            return back()
                ->withErrors(['role' => 'Kamu tidak punya akses untuk membuat user Head Admin.'])
                ->withInput();
        }

        // referrer harus punya role valid
        if (!$refRole || !isset($rankMap[$refRole])) {
            return back()
                ->withErrors(['referrer_user_id' => 'Referrer belum punya role yang valid.'])
                ->withInput();
        }

        // role baru harus dikenal
        if (!isset($rankMap[$newRole])) {
            return back()
                ->withErrors(['role' => 'Role tidak dikenali di config roles.rank'])
                ->withInput();
        }

        // rule: role baru tidak boleh lebih tinggi dari referrer
        // rank kecil = lebih tinggi, jadi newRole harus rank >= refRole
        if ($refRole === 'Health Planner') {
            $newRole = 'Health Planner';
        } else {
            if ($rankMap[$newRole] < $rankMap[$refRole]) {
                return back()
                    ->withErrors(['role' => "Role user baru tidak boleh lebih tinggi dari referrer ({$refRole})."])
                    ->withInput();
            }
        }

        // upload files (simpan path di storage/app/public/...)
        $photoPath = $request->file('photo')?->store('users/photos', 'public');
        $idCardPath = $request->file('id_card')?->store('users/id-cards', 'public');

        // create user
        $user = User::create([
            'name' => $validated['name'],
            'full_name' => $validated['full_name'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),

            'status' => $validated['status'] ?? 'Active',
            'dst_code' => $validated['dst_code'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
            'join_date' => $validated['join_date'] ?? null,
            'city_of_domicile' => $validated['city_of_domicile'] ?? null,

            'photo' => $photoPath,
            'id_card' => $idCardPath,
        ]);

        // assign role
        $user->assignRole($newRole);

        // simpan hierarchy: referrer sebagai parent, user baru sebagai child
        UserHierarchy::create([
            'parent_user_id' => $referrer->id,
            'child_user_id' => $user->id,
            'relation_type' => 'referral',
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $authUser = request()->user();

        if (!$this->isAdminLevel($authUser) && $authUser->id !== $user->id) {
            abort(403);
        }

        // Admin tidak boleh edit Head Admin
        $this->denyIfTargetIsHeadAdmin($user, $authUser);

        $user->load('roles');
        $roles = Role::query()->orderBy('name')->get();

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $authUser = request()->user();

        if (!$this->isAdminLevel($authUser) && $authUser->id !== $user->id) {
            abort(403);
        }

        // Admin tidak boleh update Head Admin
        $this->denyIfTargetIsHeadAdmin($user, $authUser);


        // =========================
        // NON-ADMIN (self update)
        // =========================
        if (!$this->isAdminLevel($authUser)) {
            $validated = $request->validate([
                'photo' => ['nullable', 'image', 'max:2048'],
                'id_card' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            ]);

            // uploads
            if ($request->hasFile('photo')) {
                $validated['photo'] = $request->file('photo')->store('users/photos', 'public');
            }

            if ($request->hasFile('id_card')) {
                $validated['id_card'] = $request->file('id_card')->store('users/id-cards', 'public');
            }

            // password
            if (empty($validated['password'] ?? null)) {
                unset($validated['password']);
            } else {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return redirect()
                ->route('users.show', $user)
                ->with('success', 'Profile berhasil diupdate.');
        }

        // =========================
        // ADMIN (full update)
        // =========================
        $validated = $request->validate([
            // basic
            'name' => ['required', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone_number' => ['nullable', 'string', 'max:30'],

            // profile/employment
            'status' => ['nullable', 'in:Active,Inactive'],
            'dst_code' => ['nullable', 'string', 'max:50'],
            'city_of_domicile' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'join_date' => ['nullable', 'date'],

            // role
            'role' => ['required', 'string', 'exists:roles,name'],

            // uploads
            'photo' => ['nullable', 'image', 'max:2048'],
            'id_card' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],

            // password
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        // uploads
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('users/photos', 'public');
        }
        if ($request->hasFile('id_card')) {
            $validated['id_card'] = $request->file('id_card')->store('users/id-cards', 'public');
        }

        // role bukan kolom users
        $role = $validated['role'];
        unset($validated['role']);

        if ($role === 'Head Admin' && !$authUser->hasRole('Head Admin')) {
            return back()
                ->withErrors(['role' => 'Kamu tidak punya akses untuk assign role Head Admin.'])
                ->withInput();
        }


        // password
        if (empty($validated['password'] ?? null)) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        $user->syncRoles([$role]);

        return redirect()->route('users.show', $user)->with('success', 'User updated successfully.');
    }

    public function searchReferrers(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $roleRanks = config('roles.rank');

        $users = User::query()
            ->with('roles')
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(12)
            ->get()
            ->map(function ($u) use ($roleRanks) {
                $role = $u->getRoleNames()->first();
                return [
                    'id'   => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $role,
                    'rank' => $role ? ($roleRanks[$role] ?? 999) : 999,
                    'label' => "{$u->name} ({$u->email}) - " . ($role ?? '-'),
                ];
            });

        return response()->json($users);
    }

    public function bulkUploadForm()
    {
        $authUser = request()->user();

        if (!$this->isAdminLevel($authUser)) {
            abort(403);
        }

        return view('users.bulk-upload');
    }

    public function bulkUploadTemplate()
    {
        $authUser = request()->user();

        if (!$this->isAdminLevel($authUser)) {
            abort(403);
        }

        // CSV header + contoh 1 baris
        $lines = [
            "name,full_name,email,password,role,referrer_email,status,dst_code,date_of_birth,phone_number,join_date,city_of_domicile",
            "Lala,Lala Herlina,lala@example.com,Password123!,Health Planner,hm@example.com,Active,DST001,1996-08-20,08123456789,2026-02-01,Jakarta",
        ];

        $csv = implode("\n", $lines) . "\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users-bulk-template.csv"',
        ]);
    }

    /**
     * Proses bulk upload CSV
     */
    public function bulkUploadStore(Request $request)
    {
        $authUser = $request->user();

        if (!$this->isAdminLevel($authUser)) {
            abort(403);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'], // max 5MB
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return back()->withErrors(['file' => 'File tidak bisa dibaca.']);
        }

        // baca header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->withErrors(['file' => 'CSV kosong / header tidak ditemukan.']);
        }

        // rapihin header jadi lowercase
        $header = array_map(fn($h) => trim(mb_strtolower((string) $h)), $header);

        $requiredHeaders = [
            'name',
            'email',
            'role',
            'referrer_email',
        ];

        foreach ($requiredHeaders as $rh) {
            if (!in_array($rh, $header, true)) {
                fclose($handle);
                return back()->withErrors(['file' => "Header wajib tidak ada: {$rh}"]);
            }
        }

        $rows = [];
        $rowNumber = 1; // header = 1
        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // skip baris kosong
            if (count(array_filter($data, fn($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = isset($data[$i]) ? trim((string) $data[$i]) : null;
            }
            $row['_row'] = $rowNumber;

            $rows[] = $row;
        }

        fclose($handle);

        if (count($rows) === 0) {
            return back()->withErrors(['file' => 'Tidak ada data user yang bisa diproses (selain header).']);
        }

        $success = [];
        $failed  = [];

        // cache referrer by email biar hemat query
        $referrerEmails = collect($rows)
            ->pluck('referrer_email')
            ->filter()
            ->map(fn($e) => mb_strtolower($e))
            ->unique()
            ->values();

        $referrersByEmail = User::query()
            ->with('roles')
            ->whereIn(DB::raw('LOWER(email)'), $referrerEmails->all())
            ->get()
            ->keyBy(fn($u) => mb_strtolower($u->email));

        $rankMap = config('roles.rank');

        foreach ($rows as $row) {
            $rowIdx = $row['_row'];

            // validasi per-row
            $v = Validator::make($row, [
                'name' => ['required', 'string', 'max:255'],
                'full_name' => ['nullable', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['nullable', 'string', 'min:8'],
                'role' => ['required', 'string', 'exists:roles,name'],
                'referrer_email' => ['required', 'email'],

                'status' => ['nullable', 'in:Active,Inactive'],
                'dst_code' => ['nullable', 'string', 'max:50'],
                'date_of_birth' => ['nullable', 'date'],
                'phone_number' => ['nullable', 'string', 'max:30'],
                'join_date' => ['nullable', 'date'],
                'city_of_domicile' => ['nullable', 'string', 'max:255'],
            ]);

            if ($v->fails()) {
                $failed[] = [
                    'row' => $rowIdx,
                    'email' => $row['email'] ?? null,
                    'errors' => $v->errors()->all(),
                ];
                continue;
            }

            $emailLower = mb_strtolower($row['email']);
            $refEmailLower = mb_strtolower($row['referrer_email']);

            // cek email duplicate existing
            if (User::query()->whereRaw('LOWER(email) = ?', [$emailLower])->exists()) {
                $failed[] = [
                    'row' => $rowIdx,
                    'email' => $row['email'],
                    'errors' => ['Email sudah terdaftar.'],
                ];
                continue;
            }

            // referrer lookup
            $referrer = $referrersByEmail->get($refEmailLower);
            if (!$referrer) {
                $failed[] = [
                    'row' => $rowIdx,
                    'email' => $row['email'],
                    'errors' => ["Referrer tidak ditemukan: {$row['referrer_email']}"],
                ];
                continue;
            }

            // enforce same rule kaya store()
            $newRoleRequested = $row['role'];

            $resolvedRoleOrError = $this->resolveRoleByReferrerRule(
                referrer: $referrer,
                requestedRole: $newRoleRequested,
                actor: $authUser,
                rankMap: $rankMap
            );

            if (is_string($resolvedRoleOrError) && str_starts_with($resolvedRoleOrError, 'ERROR:')) {
                $failed[] = [
                    'row' => $rowIdx,
                    'email' => $row['email'],
                    'errors' => [mb_substr($resolvedRoleOrError, 6)],
                ];
                continue;
            }

            $finalRole = $resolvedRoleOrError; // string role

            // password: kalau kosong, generate
            $plainPassword = $row['password'] ?: Str::random(12);

            try {
                DB::transaction(function () use ($row, $referrer, $finalRole, $plainPassword, &$success, $rowIdx) {
                    $user = User::create([
                        'name' => $row['name'],
                        'full_name' => $row['full_name'] ?: null,
                        'email' => $row['email'],
                        'password' => Hash::make($plainPassword),

                        'status' => $row['status'] ?: 'Active',
                        'dst_code' => $row['dst_code'] ?: null,
                        'date_of_birth' => $row['date_of_birth'] ?: null,
                        'phone_number' => $row['phone_number'] ?: null,
                        'join_date' => $row['join_date'] ?: null,
                        'city_of_domicile' => $row['city_of_domicile'] ?: null,
                    ]);

                    $user->assignRole($finalRole);

                    UserHierarchy::create([
                        'parent_user_id' => $referrer->id,
                        'child_user_id' => $user->id,
                        'relation_type' => 'referral',
                    ]);

                    $success[] = [
                        'row' => $rowIdx,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $finalRole,
                        'referrer_email' => $referrer->email,
                        'generated_password' => $row['password'] ? null : $plainPassword,
                    ];
                });
            } catch (\Throwable $e) {
                $failed[] = [
                    'row' => $rowIdx,
                    'email' => $row['email'],
                    'errors' => ["Gagal simpan: " . $e->getMessage()],
                ];
            }
        }

        return back()->with([
            'bulk_success' => $success,
            'bulk_failed' => $failed,
        ]);
    }

    /**
     * Reuse rule dari store():
     * - hanya Head Admin bisa create Head Admin
     * - referrer harus punya role valid (rankMap)
     * - role tidak boleh lebih tinggi dari referrer (rank kecil = lebih tinggi)
     * - kalau referrer Health Planner, role baru dipaksa Health Planner
     *
     * Return:
     * - string role final
     * - atau "ERROR:...." jika invalid
     */
    private function resolveRoleByReferrerRule(User $referrer, string $requestedRole, User $actor, array $rankMap): string
    {
        if ($requestedRole === 'Head Admin' && !$actor->hasRole('Head Admin')) {
            return 'ERROR:Kamu tidak punya akses untuk membuat user Head Admin.';
        }

        $refRole = $referrer->getRoleNames()->first();

        if (!$refRole || !isset($rankMap[$refRole])) {
            return 'ERROR:Referrer belum punya role yang valid.';
        }

        if (!isset($rankMap[$requestedRole])) {
            return 'ERROR:Role tidak dikenali di config roles.rank';
        }

        if ($refRole === 'Health Planner') {
            return 'Health Planner';
        }

        if ($rankMap[$requestedRole] < $rankMap[$refRole]) {
            return "ERROR:Role user baru tidak boleh lebih tinggi dari referrer ({$refRole}).";
        }

        return $requestedRole;
    }

    private function buildDownlineTree(int $rootUserId, string $from, string $to, bool $isManual): array
    {
        // Ambil semua edge hierarchy untuk subtree rootUserId (MySQL 8+)
        $rows = DB::select("
        WITH RECURSIVE downline AS (
            SELECT parent_user_id, child_user_id, 1 AS depth
            FROM user_hierarchies
            WHERE parent_user_id = ?

            UNION ALL

            SELECT uh.parent_user_id, uh.child_user_id, d.depth + 1
            FROM user_hierarchies uh
            INNER JOIN downline d ON uh.parent_user_id = d.child_user_id
            WHERE d.depth < 20
        )
        SELECT parent_user_id, child_user_id, depth
        FROM downline
        ORDER BY depth, parent_user_id, child_user_id
    ", [$rootUserId]);

        // Kumpulkan semua user_id yang terlibat
        $userIds = collect($rows)
            ->flatMap(fn($r) => [$r->parent_user_id, $r->child_user_id])
            ->push($rootUserId)
            ->unique()
            ->values()
            ->all();

        // Load user sekali saja
        $users = User::query()
            ->select(['id', 'name', 'phone_number', 'photo'])
            ->with('roles')
            ->whereIn('id', $userIds)
            ->where('status', 'Active')
            ->get()
            ->keyBy('id');

        // ======================================
        // Helper units (sama seperti PerformanceController)
        // ======================================
        $joinUnits = function ($q, string $soAlias = 'so') {
            return $q
                ->leftJoin('sales_order_items as soi', 'soi.sales_order_id', '=', "{$soAlias}.id")
                ->leftJoin('products as p', 'p.id', '=', 'soi.product_id')
                ->leftJoin('bundle_items as bi', 'bi.bundle_id', '=', 'p.id');
        };

        $rowUnitExpr = "CASE WHEN p.type = 'bundle' THEN soi.qty * bi.qty ELSE soi.qty END";

        // ======================================
        // Aggregate metrics per user
        // - total_key_in  => sama seperti PerformanceController summary
        // - total_net_sales => sama seperti total_sudah_install
        // - filter cutoff/manual date juga sama
        // ======================================
        $metricsQ = DB::table('sales_orders as so')
            ->whereNull('so.deleted_at')
            ->whereIn('so.sales_user_id', $userIds);

        if ($isManual) {
            $this->applyPerformanceScopeFilterForUserTree($metricsQ, $from, $to, false);
        } else {
            $this->applyPerformanceScopeFilterForUserTree($metricsQ, $from, $to, true);
        }

        $metricsQ = $joinUnits($metricsQ, 'so');

        $metricsRows = $metricsQ
            ->selectRaw("
            so.sales_user_id,

            COALESCE(SUM(
                CASE
                    WHEN COALESCE(so.is_recurring, 0) = 0
                    AND so.ccp_status = 'menunggu pengecekan'
                    AND (so.status = 'menunggu verifikasi' OR so.status = 'dibatalkan')
                    THEN {$rowUnitExpr} ELSE 0
                END
            ), 0) as total_key_in,

            COALESCE(SUM(
                CASE
                    WHEN so.status = 'selesai'
                    THEN {$rowUnitExpr} ELSE 0
                END
            ), 0) as total_net_sales
        ")
            ->groupBy('so.sales_user_id')
            ->get()
            ->keyBy('sales_user_id');

        // adjacency: parent -> [child...]
        $childrenByParent = [];
        foreach ($rows as $r) {
            $childrenByParent[$r->parent_user_id][] = $r->child_user_id;
        }

        // builder node rekursif
        $buildNode = function ($userId) use (&$buildNode, $users, $childrenByParent, $metricsRows) {
            $u = $users->get($userId);

            if (!$u) {
                return null;
            }

            $metric = $metricsRows->get($u->id);

            $children = collect($childrenByParent[$userId] ?? [])
                ->map(fn($cid) => $buildNode($cid))
                ->filter()
                ->values()
                ->all();

            return [
                'id' => $u->id,
                'name' => $u->name ?? '-',
                'phone_number' => $u->phone_number ?? '-',
                'photo' => $u->photo,
                'role' => $u->getRoleNames()->first(),

                'total_key_in' => (int) ($metric->total_key_in ?? 0),
                'total_net_sales' => (int) ($metric->total_net_sales ?? 0),

                'children' => $children,
            ];
        };

        return $buildNode($rootUserId) ?? [
            'id' => $rootUserId,
            'name' => '-',
            'phone_number' => '-',
            'photo' => null,
            'children' => [],
        ];
    }

    private function normalizeDateRange(?string $from, ?string $to): array
    {
        $from = trim((string) $from);
        $to   = trim((string) $to);

        $from = $from !== '' ? $from : null;
        $to   = $to !== '' ? $to : null;

        $isManual = (bool) ($from || $to);

        if ($isManual) {
            if ($from && !$to) $to = Carbon::parse($from)->endOfMonth()->toDateString();
            if (!$from && $to) $from = Carbon::parse($to)->startOfMonth()->toDateString();
        }

        return [$from, $to, $isManual];
    }

    private function applyPerformanceScopeFilterForUserTree($q, string $from, string $to, bool $withCarryOver = true): void
    {
        $carryFrom = Carbon::parse($from)->subMonthNoOverflow()->toDateString();

        $q->where(function ($w) use ($from, $to, $carryFrom, $withCarryOver) {
            // A. SO key-in dalam periode
            $w->where(function ($a) use ($from, $to) {
                $a->whereNotNull('so.key_in_at')
                    ->whereDate('so.key_in_at', '>=', $from)
                    ->whereDate('so.key_in_at', '<=', $to);
            });

            // B. SO selesai dan install dalam periode
            $w->orWhere(function ($a) use ($from, $to) {
                $a->where('so.status', 'selesai')
                    ->whereNotNull('so.install_date')
                    ->whereDate('so.install_date', '>=', $from)
                    ->whereDate('so.install_date', '<=', $to);
            });

            // C. Carry over recurring dari periode sebelumnya
            if ($withCarryOver) {
                $w->orWhere(function ($x) use ($carryFrom, $from) {
                    $x->whereNotNull('so.key_in_at')
                        ->whereDate('so.key_in_at', '>=', $carryFrom)
                        ->whereDate('so.key_in_at', '<', $from)
                        ->where(function ($carry) {
                            $carry
                                ->where(function ($a) {
                                    $a->whereRaw('COALESCE(so.is_recurring,0) = 1')
                                        ->where('so.ccp_status', 'menunggu pengecekan')
                                        ->where('so.status', 'menunggu verifikasi');
                                })
                                ->orWhere(function ($a) {
                                    $a->whereRaw('COALESCE(so.is_recurring,0) = 1')
                                        ->where('so.ccp_status', 'disetujui')
                                        ->where('so.status', 'menunggu jadwal');
                                })
                                ->orWhere(function ($a) {
                                    $a->whereRaw('COALESCE(so.is_recurring,0) = 1')
                                        ->where('so.ccp_status', 'disetujui')
                                        ->where('so.status', 'dijadwalkan');
                                })
                                ->orWhere(function ($a) {
                                    $a->whereRaw('COALESCE(so.is_recurring,0) = 1')
                                        ->where('so.ccp_status', 'disetujui')
                                        ->whereIn('so.status', ['ditunda', 'gagal penelponan']);
                                });
                        });
                });
            }
        });
    }

    private function isDownliner(int $rootUserId, int $targetUserId): bool
    {
        if ($rootUserId === $targetUserId) {
            return true;
        }

        // cek apakah target ada di subtree root (user_hierarchies parent->child)
        $exists = DB::selectOne("
        WITH RECURSIVE downline AS (
            SELECT child_user_id
            FROM user_hierarchies
            WHERE parent_user_id = ?

            UNION ALL

            SELECT uh.child_user_id
            FROM user_hierarchies uh
            INNER JOIN downline d ON uh.parent_user_id = d.child_user_id
        )
        SELECT 1 AS ok
        FROM downline
        WHERE child_user_id = ?
        LIMIT 1
    ", [$rootUserId, $targetUserId]);

        return !is_null($exists);
    }

    private function findHealthManagerForUser(int $userId): ?User
    {
        // naik ke atas hierarchy sampai ketemu role Health Manager
        $currentId = $userId;

        for ($i = 0; $i < 20; $i++) {
            $parentId = UserHierarchy::query()
                ->where('child_user_id', $currentId)
                ->value('parent_user_id');

            if (!$parentId) {
                return null; // sudah mentok (ga punya parent)
            }

            $parent = User::query()->with('roles')->find($parentId);
            if (!$parent) {
                return null;
            }

            if ($parent->hasRole('Health Manager')) {
                return $parent;
            }

            // kalau belum HM, lanjut naik ke parent-nya lagi
            $currentId = $parentId;
        }

        return null;
    }
}
