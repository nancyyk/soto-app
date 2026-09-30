<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    protected $fillable = ['name', 'description', 'points_required', 'stock', 'image_url', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}