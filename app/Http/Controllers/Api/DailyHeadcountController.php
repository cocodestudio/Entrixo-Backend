<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyHeadcountController extends Controller
{
    // 1. Get Courses for Dropdown
    public function getCourses(Request $request)
    {
        $courses = DB::table('courses')->select('id', 'name', 'duration_years as durationYears')->get();
        return response()->json(['status' => 'success', 'data' => $courses]);
    }

    // 2. Count Total Active Students for a Course & Semester
    public function getStudentCount(Request $request)
    {
        $count = DB::table('users')
            ->where('role', 'student')
            ->where('course_id', $request->course_id)
            ->where('current_semester', $request->semester)
            ->count();

        return response()->json(['status' => 'success', 'count' => $count]);
    }

    // 3. Fetch specific date records
    public function getByDate($date)
    {
        $headcount = DB::table('daily_headcounts')->where('date', $date)->first();
        
        if (!$headcount) {
            return response()->json(['status' => 'success', 'data' => null]);
        }

        $batches = DB::table('daily_headcount_batches')
            ->where('daily_headcount_id', $headcount->id)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'date' => $headcount->date,
                'grandTotal' => $headcount->grand_total,
                'grandPreLunch' => $headcount->grand_pre_lunch,
                'grandPostLunch' => $headcount->grand_post_lunch,
                'batches' => $batches
            ]
        ]);
    }

    // 4. Save/Update Daily Headcount with Transaction
    public function save(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'grandTotal' => 'required|integer',
            'grandPreLunch' => 'required|integer',
            'grandPostLunch' => 'required|integer',
            'batches' => 'array'
        ]);

        try {
            DB::beginTransaction();

            // Insert or Update the main record
            $existing = DB::table('daily_headcounts')->where('date', $request->date)->first();
            
            $headcountId = null;
            if ($existing) {
                DB::table('daily_headcounts')->where('id', $existing->id)->update([
                    'last_updated_by' => $request->user()->id,
                    'grand_total' => $request->grandTotal,
                    'grand_pre_lunch' => $request->grandPreLunch,
                    'grand_post_lunch' => $request->grandPostLunch,
                    'updated_at' => now()
                ]);
                $headcountId = $existing->id;
                // Delete old batches to replace with new ones cleanly
                DB::table('daily_headcount_batches')->where('daily_headcount_id', $headcountId)->delete();
            } else {
                $headcountId = DB::table('daily_headcounts')->insertGetId([
                    'date' => $request->date,
                    'last_updated_by' => $request->user()->id,
                    'grand_total' => $request->grandTotal,
                    'grand_pre_lunch' => $request->grandPreLunch,
                    'grand_post_lunch' => $request->grandPostLunch,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Insert new batches
            $batchData = [];
            foreach ($request->batches as $batch) {
                $batchData[] = [
                    'daily_headcount_id' => $headcountId,
                    'course_id' => $batch['courseId'] ?? null,
                    'course_name' => $batch['courseName'],
                    'semester' => $batch['semester'],
                    'total_students' => $batch['totalStudents'],
                    'pre_lunch' => $batch['preLunch'],
                    'post_lunch' => $batch['postLunch'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($batchData)) {
                DB::table('daily_headcount_batches')->insert($batchData);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Data saved successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}