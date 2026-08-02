<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_manual_transfer_confirmation(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['status' => 'Active']);
        $proof = UploadedFile::fake()->image('bukti-transfer.jpg');

        $response = $this->actingAs($user)->post(route('subscriptions.store'), [
            'duration_months' => 6,
            'payment_proof' => $proof,
        ]);

        $response->assertRedirect(route('subscriptions.index'));
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'duration_months' => 6,
            'amount' => 300000,
            'status' => 'pending',
        ]);

        Storage::disk('public')->assertExists(
            Subscription::where('user_id', $user->id)->value('payment_proof')
        );
    }

    public function test_non_subscriber_cannot_access_selling_kit(): void
    {
        $user = User::factory()->create(['status' => 'Active']);

        $this->actingAs($user)
            ->get(route('selling-kit.index'))
            ->assertRedirect(route('subscriptions.index'));
    }

    public function test_active_subscriber_can_access_selling_kit(): void
    {
        $user = User::factory()->create(['status' => 'Active']);

        Subscription::create([
            'user_id' => $user->id,
            'duration_months' => 3,
            'amount' => 150000,
            'status' => 'active',
            'submitted_at' => now()->subDay(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonths(3),
        ]);

        $this->actingAs($user)
            ->get(route('selling-kit.index'))
            ->assertOk()
            ->assertSee('Selling Kit');
    }
}
