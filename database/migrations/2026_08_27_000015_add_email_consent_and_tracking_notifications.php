<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->timestamp('tracking_email_consent_at')->nullable()->after('email');
            $table->timestamp('tracking_email_opted_out_at')->nullable()->after('tracking_email_consent_at');
        });

        Schema::create('delivery_tracking_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_tracking_link_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 20);
            $table->string('recipient_email', 255);
            $table->string('reply_to_email', 255)->nullable();
            $table->string('subject', 255);
            $table->string('status', 20);
            $table->string('provider_message_id', 255)->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'delivery_id', 'created_at']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_tracking_notifications');

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn(['tracking_email_consent_at', 'tracking_email_opted_out_at']);
        });
    }
};
