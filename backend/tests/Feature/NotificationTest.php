<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Machine;
use App\Models\User;
use App\Notifications\AdminRewardRedeemedNotification;
use App\Notifications\MachineFullNotification;
use App\Notifications\NewRewardAvailableNotification;
use App\Notifications\PointsAddedNotification;
use App\Notifications\RewardSuccessNotification;
use App\Notifications\StockWarningNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_machine_full_notification_is_transition_based_and_notifies_both_user_and_admin()
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create(['role' => UserRole::User]);

        $machine = Machine::create([
            'nama_lokasi' => 'SOTO #01',
            'latitude' => 0.0,
            'longitude' => 0.0,
            'is_simulation' => true,
            'kapasitas_terkini' => 10,
            'threshold_capacity' => 80,
        ]);

        // 1. normal (10) -> full (85): Send notification to BOTH admin and user
        $machine->kapasitas_terkini = 85;
        NotificationService::checkAndNotifyMachineCapacityTransition($machine, 10);
        Notification::assertSentTo($admin, MachineFullNotification::class);
        Notification::assertSentTo($user, MachineFullNotification::class);

        Notification::fake();

        // 2. full (85) -> full (90): Do not send
        $machine->kapasitas_terkini = 90;
        NotificationService::checkAndNotifyMachineCapacityTransition($machine, 85);
        Notification::assertNothingSent();

        // 3. full (90) -> normal (20): Reset state
        $machine->kapasitas_terkini = 20;
        NotificationService::checkAndNotifyMachineCapacityTransition($machine, 90);
        Notification::assertNothingSent();

        // 4. normal (20) -> full again (85): Send notification again
        $machine->kapasitas_terkini = 85;
        NotificationService::checkAndNotifyMachineCapacityTransition($machine, 20);
        Notification::assertSentTo($admin, MachineFullNotification::class);
        Notification::assertSentTo($user, MachineFullNotification::class);
    }

    public function test_notifications_api_index_and_mark_as_read_and_unread_count()
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        $user->notify(new PointsAddedNotification(
            jumlahBotol: 5,
            poinDiperoleh: 50,
            namaLokasi: 'Mesin SOTO #01',
            transactionId: 101,
        ));

        // GET /notifications
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications');
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'title' => 'Poin Bertambah',
            'description' => '+50 Poin dari Mesin Mesin SOTO #01',
        ]);

        // GET /notifications/unread-count
        $countResponse = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications/unread-count');
        $countResponse->assertStatus(200);
        $countResponse->assertJson(['unread_count' => 1]);

        $notifId = $response->json()[0]['id'];

        // PATCH /notifications/{id}/read
        $readResponse = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/notifications/{$notifId}/read");
        $readResponse->assertStatus(200);

        $this->assertNotNull($user->notifications()->find($notifId)->read_at);

        // Verify unread count is now 0
        $countResponseAfter = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications/unread-count');
        $countResponseAfter->assertJson(['unread_count' => 0]);
    }
}
