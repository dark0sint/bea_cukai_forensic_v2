<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forensic_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique(); // mis. BC-FOR-2026-0001
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('open'); // open | in_progress | closed | archived
            $table->string('priority')->default('medium'); // low | medium | high | critical
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forensic_cases');
    }
};
