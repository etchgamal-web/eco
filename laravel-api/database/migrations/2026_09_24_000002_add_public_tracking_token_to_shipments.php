<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->string('public_tracking_token', 64)->nullable()->unique()->after('tracking_number');
        });

        DB::table('shipments')->whereNull('public_tracking_token')->orderBy('id')->eachById(function (object $shipment): void {
            DB::table('shipments')->where('id', $shipment->id)->update([
                'public_tracking_token' => Str::random(48),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->dropUnique(['public_tracking_token']);
            $table->dropColumn('public_tracking_token');
        });
    }
};
