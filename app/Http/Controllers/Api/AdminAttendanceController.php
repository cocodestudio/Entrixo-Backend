<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    public function getActiveSession()
    {
        $session = DB::table('academic_sessions')->where('status', 'Active')->first();
        if (!$session) {
            return response()->json(['message' => 'No active session found'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $session], 200);
    }

    public function getStudentsAndAttendance(Request $request)
    {
        $request->validate([
            'course_id' => 'required',
            'semester' => 'required',
            'subject_id' => 'required',
            'date' => 'required|date'
        ]);

        try {
            $students = DB::table('users')
                ->where('role', 'student')
                ->where('course_id', $request->course_id)
                ->where('current_semester', $request->semester)
                ->orderBy('roll_number')
                ->select('id', 'name', 'roll_number', 'profile_pic')
                ->get();

            $attendance = DB::table('attendances')
                ->where('subject_id', $request->subject_id)
                ->where('date_key', $request->date)
                ->pluck('status', 'user_id'); 

            return response()->json([
                'status' => 'success',
                'students' => $students,
                'attendance_status' => $attendance
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching data: ' . $e->getMessage()], 500);
        }
    }

public function getDailyReport(Request $request)
{
    try {
        $date = $request->query('date');
        $courseId = $request->query('course_id');
        $semester = $request->query('semester');
        $subjectId = $request->query('subject_id');

        $students = \App\Models\User::where('role', 'student')
            ->where('course_id', $courseId)
            ->where('current_semester', $semester)
            ->orderBy('roll_number', 'asc')
            ->get();

        if ($students->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'summary' => ['total' => 0, 'present' => 0, 'absent' => 0],
                'data' => []
            ], 200);
        }

        $attendanceList = DB::table('attendances')
            ->where('date_key', $date)
            ->where('subject_id', $subjectId)
            ->get();

        $attMap = [];
        foreach ($attendanceList as $a) {
            $sid = $a->user_id ?? null;
            if ($sid) {
                $attMap[$sid] = $a;
            }
        }

        $result = [];
        $presentCount = 0;

        foreach ($students as $student) {
            $att = $attMap[$student->id] ?? null;
            $isPresent = ($att && strtolower($att->status ?? '') === 'present');
            
            if ($isPresent) $presentCount++;

            $photo = $student->profile_pic;
            if ($photo) {
                if (!filter_var($photo, FILTER_VALIDATE_URL)) {
                    $photo = asset('storage/' . $photo);
                }
            }

            $result[] = [
                'id' => $student->id,
                'name' => $student->name,
                'roll_number' => $student->roll_number ?? 'N/A',
                'profile_pic' => $photo, 
                'status' => $isPresent ? 'Present' : 'Absent',
                'details' => $att ? [
                    'marked_at' => isset($att->created_at) ? date('h:i A', strtotime($att->created_at)) : 'N/A',
                    'method'    => $att->method ?? 'QR Scan',
                    'device_name' => $att->device_name ?? 'N/A', 
                    'device_id'   => $att->device_id ?? 'N/A',
                    'ip_address'  => $att->ip_address ?? 'N/A',
                    'lab_no'      => $att->lab_no ?? 'N/A',
                    'pc_no'       => $att->pc_no ?? 'N/A',
                ] : null
            ];
        }

        return response()->json([
            'status' => 'success',
            'summary' => [
                'total' => $students->count(),
                'present' => $presentCount,
                'absent' => $students->count() - $presentCount
            ],
            'data' => $result
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}

   public function saveManualAttendance(Request $request)
    
   {
    
    $request->validate([
        'subject_id' => 'required',
        'session_id' => 'required',
        'date' => 'required|date',
        'attendance_data' => 'required|array' 
    ]);

    DB::beginTransaction();
    try {
        $dateKey = $request->date;
        $uniqueSuffix = str_replace('-', '', $dateKey);
        $now = now();

        $existingAttendance = DB::table('attendances')
            ->where('subject_id', $request->subject_id)
            ->where('date_key', $dateKey)
            ->get()
            ->keyBy('user_id'); 

        foreach ($request->attendance_data as $studentId => $status) {
            $uniqueKey = "ATT_{$studentId}_{$request->subject_id}_{$uniqueSuffix}";
        
            $existing = $existingAttendance->get($studentId);

            if ($existing && $existing->method === 'QR_SCAN' && $status === 'Present') {
                continue; 
            }

            DB::table('attendances')->updateOrInsert(
                ['unique_session_key' => $uniqueKey],
                [
                    'user_id' => $studentId,
                    'subject_id' => $request->subject_id,
                    'session_id' => $request->session_id,
                    'date_key' => $dateKey,
                    'status' => $status,
                    'device_id' => $existing ? $existing->device_id : 'ADMIN_PANEL',
                    'device_name' => $existing ? $existing->device_name : 'Manual Entry',
                    'method' => $existing ? $existing->method : 'ADMIN_MANUAL',
                    
                    'updated_at' => $now,
                    'created_at' => $existing ? $existing->created_at : $now 
                ]
            );
        }

        DB::commit();
        return response()->json(['status' => 'success', 'message' => 'Attendance Synced Safely'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
    }
    
    }
}