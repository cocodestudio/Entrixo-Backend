<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Subject;
use App\Models\AcademicSession;
use Illuminate\Http\Request;

class AcademicSetupController extends Controller {

    public function getCourses() {
        $courses = Course::withCount('subjects')->orderBy('name', 'asc')->get();
        return response()->json(['data' => $courses], 200);
    }

    public function storeCourse(Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'duration_years' => 'required|integer|min:1',
        ]);

        $course = Course::create($data);
        return response()->json(['status' => 'success', 'data' => $course], 201);
    }

    public function getSubjects(Request $request) {
        $subjects = Subject::where('course_id', $request->course_id)
            ->where('session_id', $request->session_id)
            ->where('semester', $request->semester)
            ->get();

        return response()->json(['data' => $subjects], 200);
    }

    public function storeSubject(Request $request) {
        $data = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string',
            'course_id' => 'required|exists:courses,id',
            'semester' => 'required|integer',
            'session_id' => 'required|exists:academic_sessions,id',
            'schedule' => 'required|array'
        ]);

        $session = AcademicSession::find($request->session_id);
        if (!$session || $session->status !== 'Active') {
            return response()->json(['message' => 'Session is not active'], 403);
        }

        $subject = Subject::create($data);
        return response()->json(['status' => 'success', 'data' => $subject], 201);
    }

    public function destroySubject($id) {
        Subject::findOrFail($id)->delete();
        return response()->json(['status' => 'success'], 200);
    }
}