<?php

namespace App\Http\Controllers;

use App\Models\ForensicCase;
use App\Models\EvidenceItem;
use App\Models\AnalysisResult;
use App\Models\AuditLog;
use App\Services\PythonForensicService;

class DashboardController extends Controller
{
    public function index(PythonForensicService $service)
    {
        $stats = [
            'total_cases' => ForensicCase::count(),
            'open_cases' => ForensicCase::where('status', 'open')->count(),
            'total_evidence' => EvidenceItem::count(),
            'total_analysis' => AnalysisResult::count(),
        ];

        $recentCases = ForensicCase::with(['creator', 'assignee'])
            ->latest()
            ->take(8)
            ->get();

        $recentActivity = AuditLog::with('user')
            ->latest()
            ->take(10)
            ->get();

        $serviceStatus = $service->health();

        return view('dashboard.index', compact('stats', 'recentCases', 'recentActivity', 'serviceStatus'));
    }
}
