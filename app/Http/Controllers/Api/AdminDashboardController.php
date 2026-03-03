<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        try {
            $today = Carbon::now()->toDateString();
            $now = Carbon::now();

            $totalStudents = DB::table('users')->where('role', 'student')->count();
            
            $presentToday = DB::table('attendances')
                ->where('date_key', $today)
                ->where('status', 'Present')
                ->distinct('user_id')
                ->count('user_id');

            $absentToday = DB::table('attendances')
                ->where('date_key', $today)
                ->where('status', 'Absent')
                ->distinct('user_id')
                ->count('user_id');

            $activeSession = DB::table('academic_sessions')->where('status', 'Active')->first();
            $activeLabsList = [];

            if ($activeSession) {
                // Maine users table se join hata diya hai kyunki subjects mein teacher_id nahi hai
                $subjects = DB::table('subjects')
                    ->leftJoin('courses', 'subjects.course_id', '=', 'courses.id')
                    ->select('subjects.*', 'courses.name as course_name') 
                    ->where('subjects.session_id', $activeSession->id)
                    ->get();

                foreach ($subjects as $subject) {
                    $schedule = json_decode($subject->schedule, true) ?? [];
                    foreach ($schedule as $item) {
                        if (isset($item['date']) && Carbon::parse($item['date'])->toDateString() === $today) {
                            $sTime = $item['start_time'] ?? $item['startTime'] ?? null;
                            $eTime = $item['end_time'] ?? $item['endTime'] ?? null;

                            if ($sTime && $eTime) {
                                $start = Carbon::parse($today . ' ' . $sTime);
                                $end = Carbon::parse($today . ' ' . $eTime);

                                if ($now->between($start, $end)) {
                                    $activeLabsList[] = [
                                        'id' => $subject->id,
                                        'name' => $subject->name ?? 'Unknown Lab',
                                        'courseName' => $subject->course_name ?? 'N/A',
                                        'semester' => $subject->semester,
                                        'time' => $start->format('h:i A') . ' - ' . $end->format('h:i A'),
                                        // Faculty name table mein nahi hai, toh static bhej raha hoon crash se bachne ke liye
                                        'faculty_name' => 'Admin' 
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            $alerts = DB::table('attendances')
                ->join('users', 'attendances.user_id', '=', 'users.id')
                ->join('subjects', 'attendances.subject_id', '=', 'subjects.id')
                ->where('attendances.date_key', $today)
                ->where('attendances.status', 'Absent')
                ->select('users.name', 'users.roll_number', 'subjects.name as subject_name')
                ->take(10)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'stats' => [
                        'total_students' => $totalStudents,
                        'active_labs_count' => count($activeLabsList),
                        'present_today' => $presentToday,
                        'absent_today' => $absentToday,
                    ],
                    'active_labs' => $activeLabsList,
                    'alerts' => $alerts
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}