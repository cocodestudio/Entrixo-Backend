<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lab extends Model
{
    use HasFactory;

    // Ye line check kar, ye hona zaroori hai:
    protected $fillable = ['lab_name', 'total_pcs', 'latitude', 'longitude'];
}