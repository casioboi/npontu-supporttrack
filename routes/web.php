<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HandoverController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.submit');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/my-activities', [ActivityController::class, 'myActivities'])->name('my-activities');
    Route::resource('activities', ActivityController::class)->only(['index','create','store','show']);
    Route::post('/activities/{activity}/updates', [ActivityController::class, 'update'])->name('activities.update');
    Route::get('/activity-history', HistoryController::class)->name('activity-history');
    Route::get('/handover', HandoverController::class)->name('handover');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::middleware('admin')->prefix('personnel')->name('personnel.')->group(function () {
        Route::get('/', [PersonnelController::class, 'index'])->name('index');
        Route::post('/', [PersonnelController::class, 'store'])->name('store');
        Route::put('/{user}', [PersonnelController::class, 'update'])->name('update');
        Route::patch('/{user}/toggle', [PersonnelController::class, 'toggle'])->name('toggle');
        Route::put('/{user}/password', [PersonnelController::class, 'password'])->name('password');
    });
});
