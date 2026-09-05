<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_proofs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_name', 120)->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->index(['company_id', 'submitted_at']);
        });

        Schema::create('delivery_proof_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_proof_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 16);
            $table->string('disk', 32)->default('local');
            $table->string('path');
            $table->string('original_filename', 255)->nullable();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->string('sha256', 64);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'delivery_proof_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_proof_files');
        Schema::dropIfExists('delivery_proofs');
    }
};
