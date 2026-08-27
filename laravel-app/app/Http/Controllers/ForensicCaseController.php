<?php

namespace App\Http\Controllers;

use App\Models\ForensicCase;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class ForensicCaseController extends Controller
{
    public function index()
    {
        $cases = ForensicCase::with(['creator', 'assignee', 'evidenceItems'])
            ->latest()
            ->paginate(15);

        return view('cases.index', compact('cases'));
    }

    public function create()
    {
        return view('cases.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,critical',
        ]);

        $case = ForensicCase::create([
            ...$validated,
            'case_number' => ForensicCase::generateCaseNumber(),
            'status' => 'open',
            'created_by' => auth()->id(),
            'assigned_to' => auth()->id(),
        ]);

        AuditLog::record('create_case', "Kasus baru dibuat: {$case->case_number}", $case->id);

        return redirect()->route('cases.show', $case)->with('success', 'Kasus forensik berhasil dibuat.');
    }

    public function show(ForensicCase $case)
    {
        $case->load(['evidenceItems.uploader', 'analysisResults.requester', 'auditLogs.user']);

        return view('cases.show', compact('case'));
    }

    public function updateStatus(Request $request, ForensicCase $case)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,closed,archived',
        ]);

        $case->update($validated);
        AuditLog::record('update_status', "Status kasus diubah menjadi: {$validated['status']}", $case->id);

        return back()->with('success', 'Status kasus diperbarui.');
    }
}
