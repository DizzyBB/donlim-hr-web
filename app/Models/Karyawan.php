<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Karyawan extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'nik',
        'nama',
        'email',
        'jabatan',
        'departemen',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function ujian()
    {
        return $this->hasMany(Ujian::class);
    }

    public function trainingRequests()
    {
        return $this->hasMany(TrainingRequest::class);
    }
}
