<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\AcademicSession;
use Illuminate\Http\Request;

class SubjectController extends Controller {

    public function index(Request $request) {
        $request->validate([
            'course_id' => 'required',
            'semester' => 'required'
        ]);

        $sessionId = $request->session_id;

        if (!$sessionId) {
            $activeSession = AcademicSession::where('status', 'Active')->first();
            $sessionId = $activeSession ? $activeSession->id : null;
        }

        $subjects = Subject::where('course_id', $request->course_id)
            ->where('session_id', $sessionId)
            ->where('semester', $request->semester)
            ->get();

        return response()->json(['data' => $subjects], 200);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string',
            'faculty_name' => 'nullable|string',
            'course_id' => 'required|exists:courses,id',
            'semester' => 'required|integer',
            'session_id' => 'required|exists:academic_sessions,id',
            'schedule' => 'required|array'
        ]);

        $session = AcademicSession::find($request->session_id);
        if (!$session || $session->status !== 'Active') {
            return response()->json(['message' => 'Cannot add lab to an inactive session'], 403);
        }

        $exists = Subject::where('session_id', $request->session_id)
            ->where('code', strtoupper($request->code))
            ->exists();
        
        if ($exists) {
            return response()->json(['message' => 'Subject code already exists in this session'], 409);
        }

        $subject = Subject::create([
            'name' => $data['name'],
            'code' => strtoupper($data['code']), 
            'faculty_name' => $data['faculty_name'],
            'course_id' => $data['course_id'],
            'semester' => $data['semester'],
            'session_id' => $data['session_id'],
            'schedule' => $data['schedule']
        ]);

        return response()->json(['status' => 'success', 'data' => $subject], 201);
    }

    public function destroy($id) {
        $subject = Subject::find($id);
        if (!$subject) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }
        $subject->delete();
        return response()->json(['status' => 'success'], 200);
    }
}