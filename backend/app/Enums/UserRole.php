<?php

namespace App\Enums;

enum UserRole: string
{
    case User    = 'user';
    case Admin   = 'admin';
    case Petugas = 'petugas';

    public function label(): string
    {
        return match($this) {
            UserRole::User    => 'Pengguna',
            UserRole::Admin   => 'Administrator',
            UserRole::Petugas => 'Petugas Kebersihan',
        };
    }

    public function canAccessAdmin(): bool
    {
        return $this !== UserRole::User;
    }
}
