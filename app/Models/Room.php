<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['name', 'capacity', 'location', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function trainingRequests(): HasMany
    {
        return $this->hasMany(TrainingRequest::class);
    }
}
