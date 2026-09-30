<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        return response()->json($user->notifications);
    }

    public function markAsRead(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $notif = $user->notifications()->find($id);
        if ($notif) {
            $notif->markAsRead();
        }
        return response()->json(['message' => 'Notifikasi dibaca']);
    }
}