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

    public function resetDevice(Request $request) {
    $request->validate(['student_id' => 'required|exists:users,id']);

    User::where('id', $request->student_id)->update([
        'device_id' => null,
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Device binding reset successfully. Student can now bind a new device.'
    ]);
    
    }

    public function promoteStudents(Request $request) {
        $request->validate([
            'course_id' => 'required',
            'current_semester' => 'required|integer',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:users,id'
        ]);

        $targetSemester = $request->current_semester + 1;

        User::whereIn('id', $request->student_ids)
            ->where('role', 'student')
            ->update(['current_semester' => $targetSemester]);

        return response()->json([
            'status' => 'success',
            'message' => count($request->student_ids) . ' students successfully promoted to Semester ' . $targetSemester
        ], 200);
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
                    ->get()
                    ->map(function($student) {
                        if ($student->profile_pic && !filter_var($student->profile_pic, FILTER_VALIDATE_URL)) {
                            $student->profile_pic = asset('storage/' . $student->profile_pic);
                        }
                        return $student;
                    });

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