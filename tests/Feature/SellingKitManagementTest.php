<?php

namespace Tests\Feature;

use App\Models\SellingKitCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SellingKitManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_hp_hm_and_sm_can_open_subscription_checkout(): void
    {
        foreach (['Health Planner', 'Health Manager', 'Sales Manager'] as $roleName) {
            $user = User::factory()->create(['status' => 'Active']);
            $user->assignRole(Role::firstOrCreate(['name' => $roleName]));

            $this->actingAs($user)->get(route('subscriptions.index'))->assertOk();
        }

        foreach (['Admin', 'Head Admin'] as $roleName) {
            $user = User::factory()->create(['status' => 'Active']);
            $user->assignRole(Role::firstOrCreate(['name' => $roleName]));

            $this->actingAs($user)->get(route('subscriptions.index'))->assertForbidden();
        }
    }

    public function test_admin_can_open_management_page_and_upload_to_existing_category(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['status' => 'Active']);
        $admin->assignRole(Role::firstOrCreate(['name' => 'Admin']));
        $category = SellingKitCategory::firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.selling-kit.index'))
            ->assertOk()
            ->assertSee('Manage Selling Kit')
            ->assertSee('Verifikasi Subscription');

        $response = $this->actingAs($admin)->post(route('admin.selling-kit.store'), [
            'category_id' => $category->id,
            'title' => 'Presentasi Produk Baru',
            'description' => 'Materi presentasi terbaru.',
            'file' => UploadedFile::fake()->create('presentasi.pptx', 250, 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
        ]);

        $response->assertRedirect(route('admin.selling-kit.index'));
        $this->assertDatabaseHas('selling_kit_documents', ['title' => 'Presentasi Produk Baru', 'extension' => 'pptx']);
        Storage::disk('local')->assertExists('selling-kit/uploads/presentasi-produk-baru.pptx');
    }

    public function test_head_admin_can_create_category_while_uploading_file(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['status' => 'Active']);
        $admin->assignRole(Role::firstOrCreate(['name' => 'Head Admin']));

        $this->actingAs($admin)->post(route('admin.selling-kit.store'), [
            'new_category_name' => 'Materi Follow Up',
            'new_category_description' => 'Materi untuk tindak lanjut customer.',
            'title' => 'Template Follow Up',
            'file' => UploadedFile::fake()->image('follow-up.jpg'),
        ])->assertRedirect(route('admin.selling-kit.index'));

        $this->assertDatabaseHas('selling_kit_categories', ['slug' => 'materi-follow-up']);
        $this->assertDatabaseHas('selling_kit_documents', ['slug' => 'template-follow-up', 'extension' => 'jpg']);
    }

    public function test_non_admin_cannot_manage_selling_kit(): void
    {
        $planner = User::factory()->create(['status' => 'Active']);
        $planner->assignRole(Role::firstOrCreate(['name' => 'Health Planner']));

        $this->actingAs($planner)->get(route('admin.selling-kit.index'))->assertForbidden();
    }
}
