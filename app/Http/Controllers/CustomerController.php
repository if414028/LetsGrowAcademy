<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{

    public function document(Customer $customer, string $document)
    {
        abort_unless(in_array($document, ['ktp', 'unit_barcode'], true), 404);
        $path = $customer->{$document . '_path'};
        abort_unless($path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->download($path);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $customers = Customer::query()->with('healthPlanner')
            ->where('full_name', 'like', "%{$q}%")
            ->orWhere('phone_number', 'like', "%{$q}%")
            ->orderBy('full_name')
            ->limit(10)
            ->get(['id', 'full_name', 'date_of_birth', 'phone_number', 'address', 'religion', 'unit_serial_number', 'health_planner_id'])
            ->map(fn(Customer $customer) => [
                'id' => $customer->id,
                'full_name' => $customer->full_name,
                'date_of_birth' => $customer->date_of_birth?->format('Y-m-d'),
                'phone_number' => $customer->phone_number,
                'address' => $customer->address,
                'religion' => $customer->religion,
                'unit_serial_number' => $customer->unit_serial_number,
                'health_planner_id' => $customer->health_planner_id,
                'health_planner_name' => $customer->healthPlanner?->full_name ?: $customer->healthPlanner?->name,
                'health_planner_code' => $customer->healthPlanner?->dst_code,
            ]);

        return response()->json($customers);
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $customers = Customer::with('owner.roles')
            ->where('health_planner_id', $request->user()->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhereHas('owner', fn ($owner) => $owner->where('name', 'like', "%{$search}%")
                            ->orWhere('full_name', 'like', "%{$search}%")->orWhere('dst_code', 'like', "%{$search}%"));
                });
            })->orderBy('full_name')->paginate(15)->withQueryString();
        return view('customers.index', compact('customers', 'search'));
    }

    public function create(Request $request)
    {
        return $this->form($request, new Customer());
    }

    public function edit(Request $request, Customer $customer)
    {
        return $this->form($request, $customer);
    }

    private function form(Request $request, Customer $customer)
    {
        $owners = \App\Models\User::role(['Health Planner', 'Health Manager'])
            ->where('status', 'Active')->with('roles')->orderBy('name')->get();
        return view('customers.form', compact('customer', 'owners'));
    }

    public function store(Request $request)
    {
        return $this->saveCustomer($request, new Customer());
    }

    public function update(Request $request, Customer $customer)
    {
        return $this->saveCustomer($request, $customer);
    }

    private function saveCustomer(Request $request, Customer $customer)
    {
        $isAdmin = $request->user()->hasAnyRole(['Admin', 'Head Admin']);
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'religion' => ['nullable', 'string', 'max:100'],
            'unit_serial_number' => ['nullable', 'string', 'max:255'],
            'health_planner_id' => [$isAdmin ? 'required' : 'nullable', 'integer', 'exists:users,id'],
            'ktp' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'unit_barcode' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);
        $ownerId = $isAdmin ? (int) $data['health_planner_id'] : (int) $request->user()->id;
        $paths = [];
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($customer, $data, $ownerId, &$paths) {
                app(\App\Services\CustomerOwnership::class)->validateOwner($ownerId);
                if (!$customer->exists && Customer::whereRaw('LOWER(full_name) = ?', [mb_strtolower(trim($data['full_name']))])
                    ->when(filled($data['phone_number'] ?? null), fn ($q) => $q->where('phone_number', trim($data['phone_number'])))->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['full_name' => 'Customer sudah terdaftar. Hubungi Admin untuk mengubah data atau pemiliknya.']);
                }
                $customer->fill(collect($data)->except(['ktp', 'unit_barcode'])->all());
                $customer->health_planner_id = $ownerId;
                $customer->full_name = trim($data['full_name']);
                foreach (['ktp', 'unit_barcode'] as $field) {
                    if (!empty($data[$field])) {
                        $path = $data[$field]->store('customers', 'local');
                        if (!$path) throw new \RuntimeException('Upload dokumen customer gagal.');
                        $paths[] = $path;
                        $customer->{$field . '_path'} = $path;
                    }
                }
                $customer->save();
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($paths);
            throw $e;
        }
        return redirect()->route('customers.index')->with('success', 'Data customer berhasil disimpan.');
    }
}
