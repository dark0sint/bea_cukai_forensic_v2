<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ForensicCaseController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

// ==== Guest routes ====
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ==== Authenticated routes ====
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('cases', ForensicCaseController::class)->except(['edit', 'update', 'destroy']);
    Route::patch('/cases/{case}/status', [ForensicCaseController::class, 'updateStatus'])->name('cases.status');

    Route::post('/cases/{case}/evidence', [EvidenceController::class, 'store'])->name('evidence.store');
    Route::delete('/cases/{case}/evidence/{evidence}', [EvidenceController::class, 'destroy'])->name('evidence.destroy');

    Route::post('/cases/{case}/evidence/{evidence}/analysis/anomaly', [AnalysisController::class, 'runAnomaly'])->name('analysis.anomaly');
    Route::post('/cases/{case}/evidence/{evidence}/analysis/timeline', [AnalysisController::class, 'runTimeline'])->name('analysis.timeline');
    Route::post('/cases/{case}/evidence/{evidence}/analysis/graph', [AnalysisController::class, 'runGraph'])->name('analysis.graph');
    Route::get('/cases/{case}/analysis/{analysis}', [AnalysisController::class, 'show'])->name('analysis.show');

    Route::get('/cases/{case}/report', [ReportController::class, 'generate'])->name('reports.generate');
});
