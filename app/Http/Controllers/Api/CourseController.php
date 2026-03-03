<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller {
    
    public function index() {
        $courses = Course::orderBy('name', 'asc')->get();
        return response()->json(['data' => $courses], 200); 
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'duration_years' => 'required|integer|min:1|max:10', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $course = Course::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Course created successfully',
            'data' => $course
        ], 201);
    }
}