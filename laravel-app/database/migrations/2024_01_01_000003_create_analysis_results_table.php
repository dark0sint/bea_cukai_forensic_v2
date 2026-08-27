<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forensic_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evidence_item_id')->nullable()->constrained('evidence_items')->nullOnDelete();
            $table->string('analysis_type'); // anomaly | timeline | graph
            $table->json('parameters')->nullable(); // field mapping yang dipakai
            $table->json('result')->nullable(); // hasil lengkap dari python-service
            $table->foreignId('requested_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_results');
    }
};
