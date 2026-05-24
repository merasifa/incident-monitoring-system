<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'role:admin,operator'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/history', [DashboardController::class, 'history'])->name('history.index');
    Route::redirect('/activity-logs', '/history');
    Route::get('/incidents/workflow-board', [IncidentController::class, 'workflowBoard'])->name('incidents.workflow-board');
    Route::get('/api/incidents/search', [IncidentController::class, 'search'])->name('api.incidents.search');
    Route::get('/api/notifications', [IncidentController::class, 'notifications'])->name('api.notifications');
    Route::post('/api/notifications/read', [IncidentController::class, 'notificationsRead'])->name('api.notifications.read');
    // JSON endpoint for lightweight dashboard polling
    Route::get('/api/dashboard-summary', function () {
        return response()->json(app(\App\Services\IncidentSqlService::class)->summary());
    })->name('api.dashboard.summary');
    Route::resource('incidents', IncidentController::class)->except(['destroy']);
    Route::post('/incidents/{incident}/quick-action', [IncidentController::class, 'quickAction'])->name('incidents.quick-action');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserManagementController::class)->except(['show']);
    Route::post('/users/bulk', [UserManagementController::class, 'bulkAction'])->name('users.bulk');
});

Route::delete('/incidents/{incident}', [IncidentController::class, 'destroy'])
    ->middleware(['auth', 'role:admin'])
    ->name('incidents.destroy');

require __DIR__ . '/auth.php';
