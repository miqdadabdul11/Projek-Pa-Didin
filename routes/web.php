<?php

use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\NodeTelemetryExport;

// ==========================================
// PUBLIC ROUTES (Bisa diakses tanpa login)
// ==========================================
Route::livewire('/', 'pages::auth.login')->name('login');
Route::livewire('/register', 'pages::auth.register')->name('register');

// ==========================================
// PROTECTED ROUTES (Wajib Login)
// ==========================================
Route::middleware(['auth'])->group(function () {

    // ADMIN ROUTES
    Route::middleware(['role:admin'])->group(function () {
        Route::livewire('/admin', 'pages::admin.idx')->name('admin');
        Route::livewire('/admin/client', 'pages::admin.client.idx')->name('admin.client');
    });

    // CLIENT ROUTES
    Route::middleware(['role:client'])->group(function () {
        Route::livewire('/client', 'pages::client.idx')->name('client');

        // Buildings
        Route::livewire('/client/buildings', 'pages::client.buildings')->name('client.buildings');
        Route::livewire('/client/buildings/import', 'pages::client.buildings-import')->name('client.buildings.import');

        // Rooms
        Route::livewire('/client/classrooms', 'pages::client.classrooms')->name('client.classrooms');

        // User management
        Route::livewire('/client/users/operator', 'pages::client.users.operator')->name('client.users.operator');
        Route::livewire('/client/users/maintenance', 'pages::client.users.maintenance')->name('client.users.maintenance');
        Route::livewire('/client/users/viewer', 'pages::client.users.viewer')->name('client.users.viewer');
        Route::livewire('/client/manageroles', 'pages::client.manageroles')->name('client.manageroles');
    });

    // OPERATOR ROUTES
    Route::middleware(['role:operator'])->group(function () {
        Route::livewire('/operator', 'pages::operator.idx')->name('operator');
        Route::livewire('/operator/nodes', 'pages::operator.nodes')->name('operator.nodes');
        Route::livewire('/operator/requests', 'pages::operator.requests')->name('operator.requests');
    });

    // MAINTENANCE ROUTES
    Route::middleware(['role:maintenance'])->group(function () {
        Route::livewire('/maintenance', 'pages::maintenance.idx')->name('maintenance');
        Route::livewire('/maintenance/nodes', 'pages::maintenance.nodes')->name('maintenance.nodes');
        Route::livewire('/maintenance/mqtt', 'pages::maintenance.mqtt')->name('maintenance.mqtt');
        Route::livewire('/maintenance/export', 'pages::maintenance.export')->name('maintenance.export');

        // Excel download
        Route::get('/maintenance/export/download', function () {
        $count = \App\Models\BEMS\TelemetryLog::count();
        \Log::info('Export triggered, logs count: ' . $count);
            return Excel::download(
                new NodeTelemetryExport(),
                'telemetry-' . now()->format('Ymd-His') . '.xlsx'
            );
        })->name('maintenance.export.download');
    });

    // VIEWER ROUTES
    Route::middleware(['role:viewer'])->group(function () {
        Route::livewire('/viewer', 'pages::viewer.idx')->name('viewer');
        Route::livewire('/viewer/requests', 'pages::viewer.requests')->name('viewer.requests');
        Route::livewire('/viewer/export', 'pages::viewer.export')->name('viewer.export');
        Route::livewire('/viewer/booking', 'pages::viewer.booking')->name('viewer.booking');

        // Excel download
        Route::get('/viewer/export/download', function () {
            return Excel::download(
                new NodeTelemetryExport(),
                'telemetry-' . now()->format('Ymd-His') . '.xlsx'
            );
        })->name('viewer.export.download');
    });

});
// Stop impersonate
Route::get('/impersonate/stop', function () {
    $originalId = session()->pull('impersonated_by');
    if ($originalId) {
        Auth::loginUsingId($originalId);
    }
    return redirect()->route('client');
})->middleware('auth')->name('impersonate.stop');

// Shared monitoring routes (operator, maintenance, viewer)
Route::middleware(['auth', 'role:operator|maintenance|viewer'])->group(function () {
    Route::livewire('/monitoring/buildings', 'pages::monitoring.buildings')->name('monitoring.buildings');
    Route::livewire('/monitoring/rooms', 'pages::monitoring.rooms')->name('monitoring.rooms');
    Route::livewire('/monitoring/nodes', 'pages::monitoring.nodes')->name('monitoring.nodes');
});
