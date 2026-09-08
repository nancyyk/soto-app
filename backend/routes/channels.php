<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Admin dashboard private channel ? accessible by admin and petugas only
Broadcast::channel("admin-dashboard", function ($user) {
    return $user?->role?->canAccessAdmin() ?? false;
});
