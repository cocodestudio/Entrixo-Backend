<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentDashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            date_default_timezone_set('Asia/Kolkata');
            $user = $request->user();
            $now = Carbon::now();

            // 1. ATTENDANCE STATS
            $attendanceData = DB::table('attendances')
                ->where('user_id', $user->id)
                ->selectRaw("
                    COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_count,
                    COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent_count
                ")
                ->first();

            $present = (int)($attendanceData->present_count ?? 0);
            $absent = (int)($attendanceData->absent_count ?? 0);
            $total = $present + $absent;
            $percentage = $total > 0 ? round(($present / $total), 2) : 0.0;

            // 2. UPCOMING SESSIONS
            $upcomingSessions = [];
            $activeSession = DB::table('academic_sessions')->where('status', 'Active')->first();

            if ($activeSession && $user->course_id && $user->current_semester) {
                
                $subjects = DB::table('subjects')
                    ->where('course_id', $user->course_id)
                    ->where('semester', $user->current_semester)
                    ->where('session_id', $activeSession->id)
                    ->get();

                foreach ($subjects as $subject) {
                    $schedule = is_string($subject->schedule) 
                        ? json_decode($subject->schedule, true) 
                        : $subject->schedule;

                    if (!is_array($schedule)) continue;

                    foreach ($schedule as $item) {
                        // FIX 1: TERE DB MEIN DATE ISO FORMAT MEIN HAI
                        $dateStr = $item['date'] ?? null;
                        if (!$dateStr) continue;

                        // FIX 2: TERE DB MEIN startTime aur endTime HAI (CamelCase)
                        $sTime = $item['startTime'] ?? $item['start_time'] ?? '00:00';
                        $eTime = $item['endTime'] ?? $item['end_time'] ?? '23:59';
                        
                        try {
                            // ISO Date ko clean parse karne ke liye
                            $sessionStart = Carbon::parse($dateStr)->setTimeFromTimeString($sTime);
                            $sessionEnd = Carbon::parse($dateStr)->setTimeFromTimeString($eTime);

                            // Agar aaj ki class abhi khatam nahi hui, ya future ki hai
                            if ($sessionEnd->isAfter($now)) {
                                $upcomingSessions[] = [
                                    'subject' => $subject->name,
                                    'code' => $subject->code,
                                    'faculty_name' => $subject->faculty_name ?? 'N/A',
                                    'time' => $sessionStart->format('h:i A') . ' - ' . $sessionEnd->format('h:i A'),
                                    'room' => $item['room'] ?? 'Lab',
                                    'dt_sort' => $sessionStart->timestamp,
                                    'displayDate' => $sessionStart->format('d M'),
                                    'isToday' => $sessionStart->isToday(),
                                ];
                            }
                        } catch (\Exception $e) {
                            continue; 
                        }
                    }
                }

                usort($upcomingSessions, function ($a, $b) {
                    return $a['dt_sort'] <=> $b['dt_sort'];
                });
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'attendance' => [
                        'total' => $total,
                        'present' => $present,
                        'absent' => $absent,
                        'percentage' => (double)$percentage,
                    ],
                    'upcoming_sessions' => array_values(array_slice($upcomingSessions, 0, 5))
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}