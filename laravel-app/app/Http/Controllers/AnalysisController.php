<?php

namespace App\Http\Controllers;

use App\Models\ForensicCase;
use App\Models\EvidenceItem;
use App\Models\AnalysisResult;
use App\Models\AuditLog;
use App\Services\PythonForensicService;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    public function runAnomaly(Request $request, ForensicCase $case, EvidenceItem $evidence, PythonForensicService $service)
    {
        $validated = $request->validate([
            'numeric_fields' => 'nullable|array',
            'contamination' => 'nullable|numeric|min:0.01|max:0.5',
        ]);

        $records = $evidence->records;
        $result = $service->analyzeAnomaly(
            $records,
            $validated['numeric_fields'] ?? null,
            (float) ($validated['contamination'] ?? 0.05)
        );

        $analysis = AnalysisResult::create([
            'forensic_case_id' => $case->id,
            'evidence_item_id' => $evidence->id,
            'analysis_type' => 'anomaly',
            'parameters' => $validated,
            'result' => $result,
            'requested_by' => auth()->id(),
        ]);

        AuditLog::record('run_anomaly_analysis', "Analisis anomali dijalankan pada: {$evidence->original_filename}", $case->id);

        return redirect()->route('cases.show', $case)->with('success', "Analisis anomali selesai: {$result['summary']}");
    }

    public function runTimeline(Request $request, ForensicCase $case, EvidenceItem $evidence, PythonForensicService $service)
    {
        $validated = $request->validate([
            'timestamp_field' => 'nullable|string',
            'event_field' => 'nullable|string',
            'entity_field' => 'nullable|string',
        ]);

        $result = $service->analyzeTimeline(
            $evidence->records,
            $validated['timestamp_field'] ?? null,
            $validated['event_field'] ?? null,
            $validated['entity_field'] ?? null
        );

        AnalysisResult::create([
            'forensic_case_id' => $case->id,
            'evidence_item_id' => $evidence->id,
            'analysis_type' => 'timeline',
            'parameters' => $validated,
            'result' => $result,
            'requested_by' => auth()->id(),
        ]);

        AuditLog::record('run_timeline_analysis', "Rekonstruksi timeline dijalankan pada: {$evidence->original_filename}", $case->id);

        return redirect()->route('cases.show', $case)->with('success', "Timeline selesai dibangun: {$result['summary']}");
    }

    public function runGraph(Request $request, ForensicCase $case, EvidenceItem $evidence, PythonForensicService $service)
    {
        $validated = $request->validate([
            'source_field' => 'required|string',
            'target_field' => 'required|string',
            'weight_field' => 'nullable|string',
        ]);

        $result = $service->analyzeGraph(
            $evidence->records,
            $validated['source_field'],
            $validated['target_field'],
            $validated['weight_field'] ?? null
        );

        AnalysisResult::create([
            'forensic_case_id' => $case->id,
            'evidence_item_id' => $evidence->id,
            'analysis_type' => 'graph',
            'parameters' => $validated,
            'result' => $result,
            'requested_by' => auth()->id(),
        ]);

        AuditLog::record('run_graph_analysis', "Analisis graf relasi dijalankan pada: {$evidence->original_filename}", $case->id);

        return redirect()->route('cases.show', $case)->with('success', "Analisis graf relasi selesai: {$result['summary']}");
    }

    public function show(ForensicCase $case, AnalysisResult $analysis)
    {
        return view('reports.analysis-detail', compact('case', 'analysis'));
    }
}
