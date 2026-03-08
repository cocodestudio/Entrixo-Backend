<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller {

    public function markAttendance(Request $request) {
        $user = $request->user();

        $request->validate([
            'qr_data' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'device_id' => 'required|string',
            'device_name' => 'nullable|string'
        ]);

        try {
            $qrData = json_decode($request->qr_data, true);
            $targetLabId = $qrData['l'] ?? null;
            $labNo = $qrData['l'] ?? 'N/A';
            $pcNo = $qrData['p'] ?? 'N/A';
            if (!$targetLabId) return response()->json(['message' => 'Invalid QR!'], 400);

            $activeSession = DB::table('academic_sessions')->where('status', 'Active')->first();
            if (!$activeSession) return response()->json(['message' => 'No active session!'], 400);

            $now = Carbon::now();
            $todayStr = $now->toDateString();

            $fraud = DB::table('attendances')
                ->where('date_key', $todayStr)
                ->where('device_id', $request->device_id)
                ->where('user_id', '!=', $user->id)
                ->exists();

            if ($fraud) return response()->json(['message' => 'Fraud: Device already used!'], 403);

            if (empty($user->device_id)) {
                $user->update(['device_id' => $request->device_id]);
            } else {
                if ($user->device_id !== $request->device_id) {
                    return response()->json([
                        'message' => 'Device mismatch. This account is bound to another phone. Contact Admin to reset.'
                    ], 403);
                }
            }

            $subjects = DB::table('subjects')
                ->where('course_id', $user->course_id)
                ->where('semester', $user->current_semester)
                ->get();

            $currentSubId = null;
            foreach ($subjects as $sub) {
                $sched = json_decode($sub->schedule, true) ?? [];
                foreach ($sched as $s) {
                    if (isset($s['date']) && Carbon::parse($s['date'])->toDateString() === $todayStr) {
                        $currentSubId = $sub->id;
                        break 2;
                    }
                }
            }

            if (!$currentSubId) return response()->json(['message' => 'No lab scheduled for today!'], 400);

            $lab = DB::table('labs')->where('id', $targetLabId)->first();
            if ($lab) {
                $dist = $this->calculateDistance($request->latitude, $request->longitude, $lab->latitude, $lab->longitude);
                if ($dist > 100.0) return response()->json(['message' => 'Out of range!'], 400);
            }

            $uniqueKey = "ATT_{$user->id}_{$currentSubId}_" . $now->format('Ymd');
            
            DB::table('attendances')->updateOrInsert(
                ['unique_session_key' => $uniqueKey],
                [
                    'user_id' => $user->id,
                    'device_id' => $request->device_id,
                    'device_name' => $request->device_name ?? 'Unknown',
                    'ip_address' => $request->ip(),
                    'session_id' => $activeSession->id,
                    'subject_id' => $currentSubId,
                    'lab_no' => $labNo, 
                    'pc_no' => $pcNo,
                    'date_key' => $todayStr,
                    'status' => 'Present',
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'method' => 'QR_SCAN',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            return response()->json(['status' => 'success', 'message' => 'Attendance marked!']);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        return $dist * 60 * 1.1515 * 1609.344;
    }
}