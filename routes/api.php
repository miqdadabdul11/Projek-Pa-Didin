<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\BEMS\Node;
use App\Models\BEMS\TelemetryLog; // Don't forget to import this!

Route::post('/telemetry', function (Request $request) {
    $validated = $request->validate([
        'id' => 'required|exists:nodes,id',
        'sensor_reading' => 'required|string',
        'battery' => 'required|string',
    ]);

    $node = Node::find($validated['id']);

    // 1. Update the Node for quick "Live" dashboard viewing
    $node->update([
        'sensor_reading' => $validated['sensor_reading'],
        'battery' => $validated['battery'],
        'last_status_at' => now(), 
    ]);

    // 2. Append the permanent history record!
    TelemetryLog::create([
        'node_id' => $node->id,
        'sensor_reading' => $validated['sensor_reading'],
        'battery' => $validated['battery'],
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Data logged permanently for ' . $node->name
    ], 200);
});