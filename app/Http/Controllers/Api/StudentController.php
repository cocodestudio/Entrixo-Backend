<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function getCourses() {
        $courses = Course::orderBy('name', 'asc')->get();
        return response()->json(['status' => 'success', 'data' => $courses], 200);
    }

    public function getStudents(Request $request) {
        $request->validate([
            'course_id' => 'required',
            'semester' => 'required|integer'
        ]);

        $students = User::where('role', 'student')
                        ->where('course_id', $request->course_id)
                        ->where('current_semester', $request->semester)
                        ->orderBy('name', 'asc')
                        ->get();

        return response()->json(['status' => 'success', 'data' => $students], 200);
    }

    public function updateStudent(Request $request, $id) {
        $student = User::where('role', 'student')->find($id);
        
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $student->update([
            'name' => $request->name,
            'roll_number' => strtoupper($request->roll_number),
            'course_id' => $request->course_id,
            'current_semester' => $request->current_semester,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Student updated successfully'], 200);
    }

    public function deleteStudent($id) {
        $student = User::where('role', 'student')->find($id);
        
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $student->delete();
        return response()->json(['status' => 'success', 'message' => 'Student deleted successfully'], 200);
    }
}