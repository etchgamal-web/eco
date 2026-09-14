<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_operations', function (Blueprint $table): void {
            $table->string('lease_token', 64)->nullable()->index();
            $table->timestamp('lease_expires_at')->nullable()->index();
        });

        Schema::create('provider_circuit_breakers', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 60)->unique();
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamp('opened_until')->nullable()->index();
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_circuit_breakers');
        Schema::table('payment_operations', function (Blueprint $table): void {
            $table->dropColumn(['lease_token', 'lease_expires_at']);
        });
    }
};
