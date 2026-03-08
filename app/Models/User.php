<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 
        'email', 
        'password', 
        'phone_number', 
        'roll_number', 
        'profile_pic',
        'role',
        'device_id', 
        'fcm_token', 
        'is_setup_completed', 
        'is_manual_entry',
        'course_id', 
        'current_semester', 
        'registered_by'
    ];

    protected $hidden = [
        'password', 
        'remember_token',
    ];

    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_setup_completed' => 'boolean',
            'is_manual_entry' => 'boolean',
        ];
    }

    public function course() {
        return $this->belongsTo(Course::class, 'course_id');
    }
}