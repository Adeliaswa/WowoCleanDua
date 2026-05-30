<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingLog extends Model
{
    protected $fillable = ['container_id', 'location', 'timestamp', 'description'];

    public function container()
    {
        return $this->belongsTo(Container::class, 'container_id');
    }
}