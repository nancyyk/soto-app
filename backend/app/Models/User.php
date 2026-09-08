<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        "nama",
        "email",
        "password",
        "saldo_poin",
        "role",
    ];

    protected $hidden = [
        "password",
        "remember_token",
    ];

    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password"          => "hashed",
            "saldo_poin"        => "integer",
            "role"              => UserRole::class,
        ];
    }

    // ??? Relationships ????????????????????????????????????????????????????????

    public function cardsRfid(): HasMany
    {
        return $this->hasMany(CardRfid::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // ??? Helpers ?????????????????????????????????????????????????????????????

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isPetugas(): bool
    {
        return $this->role === UserRole::Petugas;
    }
}
