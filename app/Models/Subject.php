<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model {
    protected $fillable = ['course_id', 'session_id', 'name', 'code', 'semester',
    'faculty_name', 'schedule'];
    protected $casts = [ 'schedule' => 'array' ];
    public function course() {
        return $this->belongsTo(Course::class);
    }
}