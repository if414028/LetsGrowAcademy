<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserHierarchy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

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

        // admin-level boleh lihat user siapa pun, user biasa hanya diri sendiri
        if (!$this->isAdminLevel($authUser) && $authUser->id !== $user->id) {
            abort(403);
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

        // Direct reports (bawahan langsung)
        $childHierarchies = \App\Models\UserHierarchy::with('childUser.roles')
            ->where('parent_user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        $directReports = $childHierarchies
            ->map(fn($h) => $h->childUser)
            ->filter();

        $childrenCount = $directReports->count();

        return view('users.show', compact(
            'user',
            'parentUser',
            'childrenCount',
            'directReports'
        ));
    }

    public function create()
    {
        $roles = \Spatie\Permission\Models\Role::query()->orderBy('name')->get();

        $roleRanks = config('roles.rank');

        $oldReferrer = null;
        if (old('referrer_user_id')) {
            $oldReferrer = \App\Models\User::with('roles')->find(old('referrer_user_id'));
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
}
