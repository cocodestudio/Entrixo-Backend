<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ResourceController extends Controller
{
    public function index()
    {
        $resources = Resource::orderBy('created_at', 'desc')->get();
        return response()->json([
            'status' => 'success',
            'data' => $resources
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'course_id' => 'required|string',
            'course_name' => 'required|string',
            'semester' => 'required|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,jpg,png,zip|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $fileUrl = null;
        $fileName = null;
        $fileExt = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileExt = $file->getClientOriginalExtension();
        
            $path = $file->store('resources', 'public');
            $fileUrl = asset('storage/' . $path);
        }

        $resource = Resource::create([
            'type' => $request->type,
            'title' => $request->title,
            'description' => $request->description,
            'link' => $request->link,
            'rules' => $request->rules,
            'file_url' => $fileUrl,
            'file_name' => $fileName,
            'file_extension' => $fileExt,
            'course_id' => $request->course_id,
            'course_name' => $request->course_name,
            'semester' => $request->semester,
            'uploaded_by' => 'Admin',
        ]);

        $studentsQuery = User::where('role', 'student');

        if ($request->course_id !== 'ALL') {
            $studentsQuery->where('course_id', $request->course_id);
        }
        if ($request->semester !== 'ALL') {
            $studentsQuery->where('current_semester', $request->semester);
        }

        $students = $studentsQuery->get();

        $notificationTitle = "New " . $request->type . " Uploaded";
        $notificationBody = $request->title . " is now available for " . $request->course_name;

        foreach ($students as $student) {
            NotificationService::send(
                $student->id, 
                $notificationTitle, 
                $notificationBody, 
                strtolower($request->type)
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Resource published and notifications sent!',
            'data' => $resource
        ], 201);
    }

    public function getStudentAssignments(Request $request) {
        $user = $request->user();

        if ($user->role !== 'student') {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        $assignments = Resource::where('type', 'Assignment')
            ->where(function($query) use ($user) {
                $query->where('course_id', 'ALL')
                      ->orWhere('course_id', $user->course_id);
            })
            ->where(function($query) use ($user) {
                $query->where('semester', 'ALL')
                      ->orWhere('semester', $user->current_semester);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $assignments
        ], 200);
    }

    public function getStudentResources(Request $request) {
        $user = $request->user();

        if ($user->role !== 'student') {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        $type = $request->query('type', 'Resource'); 

        $resources = Resource::where('type', $type)
            ->where(function($query) use ($user) {
                $query->where('course_id', 'ALL')
                      ->orWhere('course_id', $user->course_id);
            })
            ->where(function($query) use ($user) {
                $query->where('semester', 'ALL')
                      ->orWhere('semester', $user->current_semester);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $resources
        ], 200);
    }

    public function destroy($id)
    {
        $resource = Resource::find($id);

        if (!$resource) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        if ($resource->file_url) {
            $filePath = str_replace(asset('storage/'), '', $resource->file_url);
            Storage::disk('public')->delete($filePath);
        }

        $resource->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Resource deleted successfully'
        ], 200);
    }
}