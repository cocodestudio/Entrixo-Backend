<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = DB::table('notifications')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $notifications]);
    }

    public function markAsRead(Request $request, $id)
    {
        DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->update(['is_read' => true, 'updated_at' => now()]);

        return response()->json(['status' => 'success']);
    }

    public function clearAll(Request $request)
    {
        DB::table('notifications')->where('user_id', $request->user()->id)->delete();
        return response()->json(['status' => 'success']);
    }

    public function saveToken(Request $request)
{
    $request->validate([
        'fcm_token' => 'required|string|min:10' 
    ]);

    $request->user()->update([
        'fcm_token' => $request->fcm_token
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'FCM Token updated successfully'
    ]);
}
}