<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->string('status', 20)->default('draft');
            $t->text('excerpt')->nullable();
            $t->json('sections');
            $t->string('seo_title')->nullable();
            $t->text('seo_description')->nullable();
            $t->string('og_image_url')->nullable();
            $t->string('canonical_url')->nullable();
            $t->json('settings')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'published_at']);
        });
        Schema::create('landing_page_leads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('landing_page_id')->constrained()->cascadeOnDelete();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->text('message')->nullable();
            $t->json('metadata')->nullable();
            $t->string('source')->nullable();
            $t->string('status', 20)->default('new');
            $t->timestamps();
            $t->index(['landing_page_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_page_leads');
        Schema::dropIfExists('landing_pages');
    }
};
