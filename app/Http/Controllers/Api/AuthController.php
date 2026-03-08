<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller {
    
    public function login(Request $request) {
        $request->validate([
            'login_id' => 'required|string',
            'password' => 'required',
            'device_name' => 'required|string',
            'fcm_token' => 'nullable|string',
        ]);

        $loginId = $request->login_id;

        $user = User::where('email', $loginId)
                    ->orWhere('phone_number', $loginId)
                    ->orWhere('roll_number', $loginId)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login_id' => ['Invalid credentials provided.'],
            ]);
        }

        if ($user->role === 'admin' && $user->roll_number === strtoupper($loginId)) {
             if($user->email !== $loginId && $user->phone_number !== $loginId) {
                throw ValidationException::withMessages([
                    'login_id' => ['Admins must login using Email or Phone only.'],
                ]);
             }
        }

        if ($request->fcm_token) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        $user->tokens()->where('name', $request->device_name)->delete();

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => $user
        ], 200);
    }

    public function updateProfile(Request $request) {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', 
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->hasFile('profile_pic')) {
            if ($user->profile_pic) {
                $oldFileName = basename($user->profile_pic);
                $oldFilePath = public_path('profiles/' . $oldFileName);
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }

            $file = $request->file('profile_pic');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('profiles'), $fileName);
            $user->profile_pic = asset('profiles/' . $fileName);
        }

        $user->save();
        $user->refresh();

        return response()->json([
    'status' => 'success',
    'message' => 'Profile updated successfully',
    'user' => [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'profile_pic' => $user->profile_pic,
        'role' => $user->role,
        'phone_number' => $user->phone_number,
        'roll_number' => $user->roll_number,
        'course_name' => $user->course ? $user->course->course_name : 'N/A',
        'current_semester' => $user->current_semester,
    ]
], 200);
    }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ], 200);
    }

    public function changePassword(Request $request) {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|string|min:8|regex:/[a-z]/|regex:/[0-9]/', 
        ]);

        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'message' => 'Your old password does not match our records.'
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully!'
        ], 200);
    }

    public function register(Request $request) {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized Access. Admins only.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|unique:users,phone_number',
            'roll_number' => 'required|string|unique:users,roll_number',
            'course_id' => 'required|exists:courses,id',
            'current_semester' => 'required|integer|min:1',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make('sug@1234'), 
            'phone_number' => $validated['phone_number'],
            'roll_number' => strtoupper($validated['roll_number']),
            'role' => 'student',
            'course_id' => $validated['course_id'],
            'current_semester' => $validated['current_semester'],
            'is_setup_completed' => true,
            'is_manual_entry' => true,
            'registered_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Student registered successfully!', 
            'user' => $user
        ], 201);
    }

    public function registerAdmin(Request $request) {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized. Only admins can create other admins.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|min:10|unique:users,phone_number',
            'password' => 'required|string|min:6',
        ]);

        $admin = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'phone_number' => $validated['phone_number'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
            'is_setup_completed' => true,
            'is_manual_entry' => true,
            'registered_by' => $request->user()->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'New Admin registered successfully!', 
            'user' => $admin
        ], 201);
    }

    public function revokeAdmin(Request $request) {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized. Only admins can perform this action.'], 403);
        }

        $request->validate([
            'identifier' => 'required|string',
        ]);

        $identifier = $request->identifier;

        $targetAdmin = User::where('role', 'admin')
            ->where(function($query) use ($identifier) {
                $query->where('email', $identifier)
                      ->orWhere('phone_number', $identifier);
            })->first();

        if (!$targetAdmin) {
            return response()->json(['message' => 'No admin found with this email or phone number.'], 404);
        }

        if ($targetAdmin->id === $request->user()->id) {
            return response()->json(['message' => 'Security Error: You cannot revoke your own admin access.'], 403);
        }

        $targetAdmin->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Admin access revoked and user deleted successfully.'
        ], 200);
    }

    public function me(Request $request) {
    $user = $request->user();

    if ($user->role === 'student') {
        $user->load('course');
    }
    
    return response()->json([
        'status' => 'success',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_pic' => $user->profile_pic,
            'phone_number' => $user->phone_number,
            'role' => $user->role,
            'roll_number' => $user->roll_number,
            'course_name' => ($user->course) ? $user->course->name : 'N/A', 
            'current_semester' => $user->current_semester ?? 'N/A',
        ]
    ], 200);
    
    }
}