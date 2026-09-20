<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->string('provider_code', 60)->nullable()->after('method_code');
            $table->string('creation_status', 30)->default('creation_pending')->after('status')->index();
            $table->text('creation_error')->nullable()->after('creation_status');
            $table->timestamp('created_at_provider')->nullable()->after('creation_error');
            $table->index(['order_id', 'provider_code']);
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->dropIndex(['order_id', 'provider_code']);
            $table->dropColumn(['provider_code', 'creation_status', 'creation_error', 'created_at_provider']);
        });
    }
};
