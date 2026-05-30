<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Container extends Model
{
    protected $fillable = ['container_id', 'waste_type', 'weight_kg', 'status'];

    public function trackingLogs()
    {
        return $this->hasMany(TrackingLog::class, 'container_id');
    }
}