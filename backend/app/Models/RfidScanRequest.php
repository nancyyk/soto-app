<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RfidScanRequest extends Model
{
    protected $fillable = ['user_id', 'device_id', 'status', 'rfid_uid', 'expires_at'];
    protected $casts = [
        'expires_at' => 'datetime',
    ];
}