<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_monitoring_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('rule_type', 60)->unique();
            $table->unsignedInteger('days');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
        DB::table('order_monitoring_settings')->insert(array_map(static fn (array $setting): array => ['rule_type' => $setting[0], 'days' => $setting[1], 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()], [['review_overdue', 1], ['processing_overdue', 2], ['shipment_no_update', 5], ['delivery_overdue', 2], ['settlement_missing', 3], ['return_settlement_missing', 3]]));
        Schema::create('operational_alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('customer_orders')->cascadeOnDelete();
            $table->string('type', 60);
            $table->string('severity', 20)->default('medium');
            $table->string('status', 20)->default('open');
            $table->timestamp('detected_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['status', 'type']);
            $table->index(['order_id', 'type', 'status']);
        });
        Schema::create('operational_alert_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('operational_alert_id')->constrained('operational_alerts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('sent_at');
            $table->unique(['operational_alert_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_alert_notifications');
        Schema::dropIfExists('operational_alerts');
        Schema::dropIfExists('order_monitoring_settings');
    }
};
