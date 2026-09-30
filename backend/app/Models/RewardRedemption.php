<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RewardRedemption extends Model
{
    protected $fillable = ['user_id', 'reward_id', 'points_used', 'status', 'shipping_address', 'tracking_number'];
    
    public function user() {
        return $this->belongsTo(User::class);
    }
    public function reward() {
        return $this->belongsTo(Reward::class);
    }
}