<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model {
    protected $fillable = ['name', 'branch', 'duration_years'];
    public function subjects() { 
        return $this->hasMany(Subject::class); 
    }
}