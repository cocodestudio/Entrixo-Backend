<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use Illuminate\Http\Request;

class LabController extends Controller {
    
    public function index() {
        $labs = Lab::orderBy('created_at', 'desc')->get();
        return response()->json([
            'status' => 'success', 
            'labs' => $labs
        ], 200);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'lab_name' => 'required|string|max:255',
            'total_pcs' => 'required|integer|min:1',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $lab = Lab::create([
            'lab_name' => $validated['lab_name'],
            'total_pcs' => $validated['total_pcs'],
            'latitude' => $validated['latitude'] ?? 0.0,
            'longitude' => $validated['longitude'] ?? 0.0,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Lab created successfully', 'data' => $lab], 201);
    }

    public function destroy($id) {
        $lab = Lab::find($id);
        if (!$lab) {
            return response()->json(['status' => 'error', 'message' => 'Lab not found'], 404);
        }
        
        $lab->delete();
        return response()->json(['status' => 'success', 'message' => 'Lab deleted successfully'], 200);
    }
}