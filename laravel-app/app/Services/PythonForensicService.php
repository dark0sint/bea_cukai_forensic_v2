<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Client HTTP terpusat untuk berkomunikasi dengan Python Forensic Analysis Service.
 * Semua request disertai header X-API-Key (shared secret) untuk autentikasi service-to-service.
 */
class PythonForensicService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('forensic.base_url'), '/');
        $this->apiKey = config('forensic.api_key');
        $this->timeout = (int) config('forensic.timeout', 120);
    }

    protected function client()
    {
        return Http::withHeaders(['X-API-Key' => $this->apiKey])
            ->timeout($this->timeout)
            ->baseUrl($this->baseUrl);
    }

    public function health(): array
    {
        $response = Http::timeout(10)->get("{$this->baseUrl}/api/v1/health");
        return $response->successful() ? $response->json() : ['status' => 'unreachable'];
    }

    /** Upload file barang bukti untuk di-hash & di-parsing oleh service Python. */
    public function uploadEvidence(UploadedFile $file): array
    {
        $response = $this->client()
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post('/api/v1/evidence/upload');

        return $this->handle($response, 'upload evidence');
    }

    public function analyzeAnomaly(array $records, ?array $numericFields = null, float $contamination = 0.05): array
    {
        $response = $this->client()->post('/api/v1/analysis/anomaly', [
            'records' => $records,
            'numeric_fields' => $numericFields,
            'contamination' => $contamination,
        ]);

        return $this->handle($response, 'analisis anomali');
    }

    public function analyzeTimeline(array $records, ?string $timestampField = null, ?string $eventField = null, ?string $entityField = null): array
    {
        $response = $this->client()->post('/api/v1/analysis/timeline', [
            'records' => $records,
            'timestamp_field' => $timestampField,
            'event_field' => $eventField,
            'entity_field' => $entityField,
        ]);

        return $this->handle($response, 'analisis timeline');
    }

    public function analyzeGraph(array $records, string $sourceField, string $targetField, ?string $weightField = null): array
    {
        $response = $this->client()->post('/api/v1/analysis/graph', [
            'records' => $records,
            'source_field' => $sourceField,
            'target_field' => $targetField,
            'weight_field' => $weightField,
        ]);

        return $this->handle($response, 'analisis graph relasi');
    }

    /** Generate laporan PDF. Mengembalikan raw bytes PDF. */
    public function generateReportPdf(array $context): string
    {
        $response = $this->client()->post('/api/v1/reports/generate', array_merge($context, ['format' => 'pdf']));

        if (!$response->successful()) {
            Log::error('Forensic report generation failed', ['body' => $response->body()]);
            throw new RuntimeException('Gagal membuat laporan PDF dari forensic service.');
        }

        return $response->body();
    }

    protected function handle($response, string $action): array
    {
        if (!$response->successful()) {
            Log::error("Forensic service error saat {$action}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException("Gagal melakukan {$action}: " . ($response->json('detail') ?? $response->status()));
        }

        return $response->json();
    }
}
