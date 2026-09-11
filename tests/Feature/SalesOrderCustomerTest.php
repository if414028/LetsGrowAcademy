<?php

namespace Tests\Feature;

use App\Models\{Customer, Product, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Storage};
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesOrderCustomerTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        foreach (['Admin', 'Health Manager', 'Health Planner'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $admin = User::factory()->create(['status' => 'Active']);
        $admin->assignRole('Admin');
        $this->actingAs($admin);
        $hm = User::factory()->create(['status' => 'Active']);
        $hm->assignRole('Health Manager');
        $hp = User::factory()->create(['status' => 'Active']);
        $hp->assignRole('Health Planner');
        DB::table('user_hierarchies')->insert(['parent_user_id' => $hm->id, 'child_user_id' => $hp->id]);
        $product = Product::create(['sku' => 'TEST', 'product_name' => 'Unit', 'model' => 'Test', 'type' => 'regular']);
        $price = $product->prices()->create(['label' => 'Price', 'billing_type' => 'one_time', 'amount' => 100]);
        return [
            'order_no' => 'SO-TEST', 'health_manager_id' => $hm->id, 'sales_user_id' => $hp->id,
            'customer_name' => 'Customer Test', 'customer_address' => 'Jakarta',
            'customer_type' => 'individu', 'status' => 'menunggu verifikasi', 'ccp_status' => 'menunggu pengecekan',
            'items' => [['product_id' => $product->id, 'product_price_id' => $price->id, 'qty' => 1]],
        ];
    }

    public function test_optional_fields_can_be_empty_and_customer_gets_order_hp(): void
    {
        $data = $this->payload();
        $this->get(route('sales-orders.create'))->assertOk()->assertSee('Upload KTP')->assertSee('customer_previous_hp_id');
        $this->post(route('sales-orders.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('customers', ['full_name' => 'Customer Test', 'health_planner_id' => $data['sales_user_id'], 'religion' => null]);
    }

    public function test_documents_and_profile_are_saved_privately(): void
    {
        Storage::fake('local');
        $data = $this->payload() + ['customer_religion' => 'Islam', 'customer_unit_serial_number' => 'SN-001',
            'customer_ktp' => UploadedFile::fake()->create('ktp.pdf', 20, 'application/pdf'),
            'customer_unit_barcode' => UploadedFile::fake()->create('barcode.pdf', 20, 'application/pdf')];
        $this->post(route('sales-orders.store'), $data)->assertSessionHasNoErrors();
        $customer = Customer::firstOrFail();
        $this->get(route('sales-orders.show', \App\Models\SalesOrder::firstOrFail()))->assertOk()->assertSee('SN-001')->assertSee('Unduh KTP');
        $this->assertSame('SN-001', $customer->unit_serial_number);
        Storage::disk('local')->assertExists([$customer->ktp_path, $customer->unit_barcode_path]);
        $this->get(route('customers.document', [$customer, 'ktp']))->assertOk();
        $this->actingAs(User::factory()->create(['status' => 'Active']))->get(route('customers.document', [$customer, 'ktp']))->assertForbidden();
    }

    public function test_transfer_requires_current_owner_to_have_been_shown(): void
    {
        $data = $this->payload();
        $previous = User::factory()->create(['status' => 'Active']);
        $customer = Customer::create(['full_name' => 'Customer Test', 'health_planner_id' => $previous->id, 'religion' => 'Islam']);
        $data['customer_id'] = $customer->id;
        $this->post(route('sales-orders.store'), $data)->assertSessionHasErrors('customer_id');
        $this->assertEquals($previous->id, $customer->fresh()->health_planner_id);
        $this->assertDatabaseCount('sales_orders', 0);
        $data['customer_previous_hp_id'] = $previous->id;
        $this->post(route('sales-orders.store'), $data)->assertSessionHasNoErrors();
        $this->assertEquals($data['sales_user_id'], $customer->fresh()->health_planner_id);
        $this->assertSame('Islam', $customer->fresh()->religion);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_new_order_transfers_owner_without_changing_previous_order_hp(): void
    {
        $data = $this->payload();
        $this->post(route('sales-orders.store'), $data)->assertSessionHasNoErrors();
        $oldOrder = \App\Models\SalesOrder::firstOrFail();
        $newHp = User::factory()->create(['status' => 'Active']);
        $newHp->assignRole('Health Planner');
        DB::table('user_hierarchies')->insert(['parent_user_id' => $data['health_manager_id'], 'child_user_id' => $newHp->id]);
        $data['order_no'] = 'SO-SECOND';
        $data['customer_id'] = $oldOrder->customer_id;
        $data['customer_previous_hp_id'] = $data['sales_user_id'];
        $data['sales_user_id'] = $newHp->id;
        $this->post(route('sales-orders.store'), $data)->assertSessionHasNoErrors();
        $this->assertEquals($data['customer_previous_hp_id'], $oldOrder->fresh()->sales_user_id);
        $this->assertEquals($newHp->id, $oldOrder->customer->health_planner_id);
    }

    public function test_so_cannot_assign_second_customer_but_can_repeat_for_same_customer(): void
    {
        $data = $this->payload();
        $this->post(route('sales-orders.store'), $data)->assertSessionHasNoErrors();
        $data['order_no'] = 'SO-REPEAT';
        $this->post(route('sales-orders.store'), $data)->assertSessionHasNoErrors();
        $data['order_no'] = 'SO-SECOND-CUSTOMER';
        $data['customer_name'] = 'Another Customer';
        $this->post(route('sales-orders.store'), $data)->assertSessionHasErrors('sales_user_id');
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('sales_orders', 2);
    }

    public function test_invalid_upload_does_not_create_customer_or_order(): void
    {
        $data = $this->payload() + ['customer_ktp' => UploadedFile::fake()->create('script.exe', 10)];
        $this->post(route('sales-orders.store'), $data)->assertSessionHasErrors('customer_ktp');
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('sales_orders', 0);
    }
}
