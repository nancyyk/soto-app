<?php

namespace App\Enums;

enum RouteStatus: string
{
    case Pending = 'pending';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            RouteStatus::Pending => 'Menunggu',
            RouteStatus::Selesai => 'Selesai',
        };
    }
}
