<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSession extends Model {
    protected $fillable = [
        'session_name',
        'academic_year',
        'start_date',
        'end_date',
        'course_id',
        'course_name',
        'target_semester',
        'description',
        'status'
    ];
}