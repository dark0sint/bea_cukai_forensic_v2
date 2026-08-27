<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forensic_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('original_filename');
            $table->string('stored_filename'); // nama file di storage python-service
            $table->string('file_type', 20); // csv | json | xml | pdf
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('sha256', 64)->nullable();
            $table->string('md5', 32)->nullable();
            $table->json('parse_result')->nullable(); // hasil parsing dari python-service (ringkas)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_items');
    }
};
