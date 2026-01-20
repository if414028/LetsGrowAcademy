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
    public function index()
    {
        $users = User::query()
            ->with('roles')
            ->latest()
            ->paginate(10);

        return view('users.index', compact('users'));
    }

    public function show(User $user)
    {
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
            ->map(fn ($h) => $h->childUser)
            ->filter(); // remove null just in case

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

        $referrers = \App\Models\User::query()
            ->with('roles')
            ->orderBy('name')
            ->get();

        $roleRanks = config('roles.rank'); // ['Admin'=>1, ... ]

        return view('users.create', compact('roles', 'referrers', 'roleRanks'));
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
        $user->load('roles');
        $roles = Role::query()->orderBy('name')->get();

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', 'string', 'exists:roles,name'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        // update password kalau diisi
        if (!empty($validated['password'])) {
            $user->update(['password' => \Illuminate\Support\Facades\Hash::make($validated['password'])]);
        }

        // set role (single role)
        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }
}
