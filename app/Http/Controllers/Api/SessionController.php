<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcademicSession;

class SessionController extends Controller {
    
    public function index() {
        $sessions = AcademicSession::orderBy('created_at', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $sessions], 200);
    }

    public function store(Request $request) {
        $request->validate([
            'session_name' => 'required|string|unique:academic_sessions,session_name',
            'academic_year' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'course_id' => 'required|string',
            'course_name' => 'required|string',
            'target_semester' => 'required|string',
            'description' => 'required|string'
        ]);

        AcademicSession::where('status', 'Active')->update(['status' => 'Inactive']);

        $session = AcademicSession::create(array_merge(
            $request->all(),
            ['status' => 'Active']
        ));

        return response()->json(['status' => 'success', 'data' => $session], 201);
    }

    public function destroy($id) {
        $session = AcademicSession::find($id);
        if (!$session) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }
        $session->delete();
        return response()->json(['status' => 'success'], 200);
    }
}