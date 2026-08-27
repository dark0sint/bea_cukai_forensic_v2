<?php

namespace App\Http\Controllers;

use App\Models\ForensicCase;
use App\Models\EvidenceItem;
use App\Models\AuditLog;
use App\Services\PythonForensicService;
use Illuminate\Http\Request;

class EvidenceController extends Controller
{
    public function store(Request $request, ForensicCase $case, PythonForensicService $service)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,json,xml,pdf|max:51200', // max 50MB
        ]);

        $uploadResult = $service->uploadEvidence($request->file('file'));

        $evidence = EvidenceItem::create([
            'forensic_case_id' => $case->id,
            'uploaded_by' => auth()->id(),
            'original_filename' => $uploadResult['filename'],
            'stored_filename' => $uploadResult['stored_as'],
            'file_type' => $uploadResult['file_type'],
            'size_bytes' => $uploadResult['hashes']['size_bytes'] ?? 0,
            'sha256' => $uploadResult['hashes']['sha256'] ?? null,
            'md5' => $uploadResult['hashes']['md5'] ?? null,
            'parse_result' => $uploadResult['parse_result'],
        ]);

        AuditLog::record(
            'upload_evidence',
            "Barang bukti diunggah: {$evidence->original_filename} (SHA-256: {$evidence->sha256})",
            $case->id
        );

        return redirect()->route('cases.show', $case)->with('success', 'Barang bukti berhasil diunggah dan diproses.');
    }

    public function destroy(ForensicCase $case, EvidenceItem $evidence)
    {
        $filename = $evidence->original_filename;
        $evidence->delete();

        AuditLog::record('delete_evidence', "Barang bukti dihapus: {$filename}", $case->id);

        return back()->with('success', 'Barang bukti dihapus dari kasus.');
    }
}
