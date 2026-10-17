<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reviewer_name', 120);
            $table->string('reviewer_email');
            $table->unsignedTinyInteger('rating');
            $table->string('title', 160)->nullable();
            $table->text('content');
            $table->string('image_path')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->boolean('is_verified_purchase')->default(false);
            $table->text('store_response')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'status', 'published_at']);
            $table->unique(['product_id', 'reviewer_email']);
            $table->unique(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};