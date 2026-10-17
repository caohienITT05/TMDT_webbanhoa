<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_review_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_review_id')->nullable()->constrained('product_reviews')->nullOnDelete();
            $table->unsignedBigInteger('reviewer_user_id')->index();
            $table->unsignedTinyInteger('previous_rating');
            $table->text('previous_content');
            $table->string('previous_image_path')->nullable();
            $table->unsignedTinyInteger('new_rating');
            $table->text('new_content');
            $table->string('new_image_path')->nullable();
            $table->string('previous_status', 20);
            $table->string('new_status', 20);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['reviewer_user_id', 'created_at'], 'prr_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_review_revisions');
    }
};
