<?php

namespace App\Http\Controllers;

use App\Models\ForensicCase;
use App\Models\AuditLog;
use App\Services\PythonForensicService;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    public function generate(ForensicCase $case, PythonForensicService $service)
    {
        $case->load(['evidenceItems', 'analysisResults']);

        $anomalyResult = optional($case->analysisResults->where('analysis_type', 'anomaly')->last())->result;
        $timelineResult = optional($case->analysisResults->where('analysis_type', 'timeline')->last())->result;
        $graphResult = optional($case->analysisResults->where('analysis_type', 'graph')->last())->result;

        $context = [
            'case_name' => "{$case->case_number} - {$case->title}",
            'analyst' => auth()->user()->name,
            'summary' => $case->description,
            'evidence_list' => $case->evidenceItems->map(fn ($e) => [
                'filename' => $e->original_filename,
                'sha256' => $e->sha256,
                'size_bytes' => $e->size_bytes,
                'uploaded_at' => $e->created_at->toDateTimeString(),
            ])->toArray(),
            'anomaly_result' => $anomalyResult,
            'timeline_result' => $timelineResult,
            'graph_result' => $graphResult,
        ];

        $pdfContent = $service->generateReportPdf($context);

        AuditLog::record('generate_report', "Laporan forensik PDF dibuat untuk kasus {$case->case_number}", $case->id);

        return Response::make($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"laporan-{$case->case_number}.pdf\"",
        ]);
    }
}
