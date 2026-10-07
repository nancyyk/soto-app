<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /api/v1/notifications
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $notifications = $user->notifications()->latest()->get()->map(function ($notif) {
            $data = is_array($notif->data) ? $notif->data : (json_decode($notif->data, true) ?? []);

            return [
                'id' => $notif->id,
                'type' => $data['type'] ?? $notif->type,
                'title' => $data['title'] ?? 'Notifikasi',
                'description' => $data['description'] ?? ($data['message'] ?? ''),
                'data' => $data,
                'read_at' => $notif->read_at ? $notif->read_at->toIso8601String() : null,
                'created_at' => $notif->created_at ? $notif->created_at->toIso8601String() : null,
            ];
        });

        return response()->json($notifications);
    }

    /**
     * GET /api/v1/notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * PATCH /api/v1/notifications/{id}/read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $notif = $user->notifications()->find($id);
        if ($notif) {
            $notif->markAsRead();
        }

        return response()->json(['message' => 'Notifikasi dibaca']);
    }

    /**
     * PATCH /api/v1/notifications/read-all
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Semua notifikasi dibaca']);
    }
}