<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table): void {
            $table->timestamp('delivery_window_starts_at')->nullable()->after('scheduled_at');
            $table->timestamp('delivery_window_ends_at')->nullable()->after('delivery_window_starts_at');
            $table->index(['company_id', 'delivery_window_starts_at']);
        });

        Schema::create('delivery_tracking_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->unique('delivery_id');
            $table->index(['company_id', 'revoked_at']);
        });

        Schema::create('delivery_tracking_link_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_tracking_link_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 32);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'delivery_tracking_link_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_tracking_link_events');
        Schema::dropIfExists('delivery_tracking_links');

        Schema::table('deliveries', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'delivery_window_starts_at']);
            $table->dropColumn(['delivery_window_starts_at', 'delivery_window_ends_at']);
        });
    }
};
