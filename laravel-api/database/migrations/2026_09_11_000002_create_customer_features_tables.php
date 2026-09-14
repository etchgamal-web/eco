<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('customer_addresses', function (Blueprint $table) {
   $table->id(); $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
   $table->string('label')->default('home'); $table->string('recipient_name'); $table->string('phone', 30);
   $table->string('address_line1'); $table->string('address_line2')->nullable(); $table->string('city');
   $table->string('state')->nullable(); $table->string('postal_code', 30)->nullable(); $table->string('country', 2)->default('EG');
   $table->boolean('is_default')->default(false); $table->timestamps(); $table->index(['user_id','is_default']);
  });
  Schema::create('customer_orders', function (Blueprint $table) {
   $table->id(); $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
   $table->string('status', 30)->default('pending')->index(); $table->unsignedBigInteger('total_amount')->default(0); $table->string('currency', 3)->default('EGP'); $table->timestamps();
  });
  Schema::create('customer_order_items', function (Blueprint $table) {
   $table->id(); $table->foreignId('order_id')->constrained('customer_orders')->cascadeOnDelete(); $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
   $table->string('name'); $table->unsignedInteger('quantity'); $table->unsignedBigInteger('unit_price'); $table->timestamps();
  });
  Schema::create('customer_carts', function (Blueprint $table) { $table->id(); $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete(); $table->timestamps(); });
  Schema::create('customer_cart_items', function (Blueprint $table) {
   $table->id(); $table->foreignId('cart_id')->constrained('customer_carts')->cascadeOnDelete(); $table->foreignId('product_id')->constrained('products')->cascadeOnDelete(); $table->unsignedInteger('quantity')->default(1); $table->timestamps(); $table->unique(['cart_id','product_id']);
  });
  Schema::create('customer_wishlists', function (Blueprint $table) {
   $table->id(); $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); $table->foreignId('product_id')->constrained('products')->cascadeOnDelete(); $table->timestamps(); $table->unique(['user_id','product_id']);
  });
  Schema::create('customer_preferences', function (Blueprint $table) { $table->id(); $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete(); $table->json('data')->nullable(); $table->timestamps(); });
  Schema::create('customer_notifications', function (Blueprint $table) { $table->id(); $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); $table->string('type'); $table->string('title'); $table->text('body')->nullable(); $table->timestamp('read_at')->nullable(); $table->timestamps(); $table->index(['user_id','read_at']); });
 }
 public function down(): void { foreach (['customer_notifications','customer_preferences','customer_wishlists','customer_cart_items','customer_carts','customer_order_items','customer_orders','customer_addresses'] as $table) Schema::dropIfExists($table); }
};
