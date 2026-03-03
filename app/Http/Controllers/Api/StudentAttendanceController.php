<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentAttendanceController extends Controller
{
    public function getSessions(Request $request)
    {
        $sessions = DB::table('academic_sessions')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => (string) $session->id,
                    'name' => $session->session_name . ($session->academic_year ? " ({$session->academic_year})" : "")
                ];
            });
        return response()->json(['status' => 'success', 'data' => $sessions]);
    }

    public function getAttendance(Request $request)
    {
        try {
            $user = $request->user();
            $sessionId = $request->query('session_id');

            // 1. Get Active Session
            $session = $sessionId 
                ? DB::table('academic_sessions')->where('id', $sessionId)->first()
                : DB::table('academic_sessions')->where('status', 'Active')->first();

            if (!$session) {
                return $this->emptyResponse("No Active Session Found");
            }

            // 2. Fetch Subjects based on Course & Semester (Matching your markAttendance logic)
            $subjects = DB::table('subjects')
                ->where('course_id', $user->course_id)
                ->where('semester', $user->current_semester)
                ->where('session_id', $session->id)
                ->get();

            // 3. Fetch all attendance records for this student in this session
            $attendances = DB::table('attendances')
                ->where('user_id', $user->id)
                ->where('session_id', $session->id)
                ->get();

            // Group attendance by subject_id and date_key for quick lookup
            $attendanceMap = [];
            foreach ($attendances as $att) {
                $key = $att->subject_id . '_' . $att->date_key;
                $attendanceMap[$key] = $att->status;
            }

            $finalSubjects = [];
            $totalClassesAll = 0;
            $totalPresentAll = 0;
            $now = Carbon::now();

            foreach ($subjects as $subject) {
                $schedule = json_decode($subject->schedule, true) ?? [];
                $subjectTotal = 0;
                $subjectPresent = 0;
                $sessionHistory = [];

                foreach ($schedule as $item) {
                    if (!isset($item['date'])) continue;

                    $dateObj = Carbon::parse($item['date']);
                    $dateKey = $dateObj->toDateString();
                    
                    $sTime = $item['start_time'] ?? $item['startTime'] ?? '00:00';
                    $eTime = $item['end_time'] ?? $item['endTime'] ?? '23:59';
                    
                    $labEndTime = Carbon::parse($dateKey . ' ' . $eTime);

                    // Check status
                    $attLookupKey = $subject->id . '_' . $dateKey;
                    $status = 'Upcoming';

                    if (isset($attendanceMap[$attLookupKey])) {
                        $status = $attendanceMap[$attLookupKey];
                    } elseif ($labEndTime->isPast()) {
                        $status = 'Absent';
                    }

                    // Calculation
                    if ($status === 'Present' || $status === 'Absent') {
                        $subjectTotal++;
                        if ($status === 'Present') $subjectPresent++;
                    }

                    $sessionHistory[] = [
                        'date' => $dateObj->toIso8601String(),
                        'startTime' => $sTime,
                        'endTime' => $eTime,
                        'status' => $status,
                        'topic' => $item['topic'] ?? $subject->name,
                    ];
                }

                $totalClassesAll += $subjectTotal;
                $totalPresentAll += $subjectPresent;

                $finalSubjects[] = [
    'id' => (string) ($subject->id ?? ''),
    'name' => (string) ($subject->name ?? 'Unknown'),
    'code' => (string) ($subject->code ?? '---'),
    'faculty' => (string) ($subject->faculty_name ?? 'Faculty'), 
    'total' => (int) $subjectTotal,
    'attended' => (int) $subjectPresent,
    'sessions' => $sessionHistory,
];
            }

            $overallPer = $totalClassesAll > 0 ? ($totalPresentAll / $totalClassesAll) : 0.0;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'subjects' => $finalSubjects,
                    'overallPercentage' => (double)$overallPer,
                    'totalClasses' => $totalClassesAll,
                    'totalPresent' => $totalPresentAll,
                    'activeSessionId' => (string)$session->id,
                    'activeSessionName' => $session->session_name
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Line ' . $e->getLine() . ': ' . $e->getMessage()
            ], 500);
        }
    }

    private function emptyResponse($msg) {
        return response()->json([
            'status' => 'success',
            'data' => [
                'subjects' => [], 'overallPercentage' => 0.0,
                'totalClasses' => 0, 'totalPresent' => 0,
                'activeSessionId' => '', 'activeSessionName' => $msg,
            ]
        ]);
    }
}