<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('personnel_label_singular', 60)->default('Driver');
            $table->string('personnel_label_plural', 60)->default('Drivers');
            $table->string('operation_label_singular', 60)->default('Delivery');
            $table->string('operation_label_plural', 60)->default('Deliveries');
            $table->string('time_zone', 64)->default('UTC');
            $table->boolean('tracking_enabled')->default(true);
            $table->timestamps();
            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
