<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_review_negative_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_review_id')->nullable()->constrained('product_reviews')->nullOnDelete();
            $table->unique('product_review_id');
            $table->unsignedBigInteger('reviewer_user_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedTinyInteger('rating');
            $table->string('event_type', 40)->default('submitted');
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->index(['reviewer_user_id', 'recorded_at'], 'prne_user_recorded_idx');
        });

        Schema::create('product_review_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_review_id')->constrained('product_reviews')->cascadeOnDelete();
            $table->unsignedBigInteger('reporter_user_id')->index();
            $table->string('reason', 40);
            $table->text('details')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable()->index();
            $table->timestamps();
            $table->unique(['product_review_id', 'reporter_user_id']);
        });

        Schema::create('product_review_moderation_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_review_id')->nullable()->constrained('product_reviews')->nullOnDelete();
            $table->unsignedBigInteger('moderator_user_id')->nullable()->index();
            $table->string('from_status', 20);
            $table->string('to_status', 20);
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['product_review_id', 'created_at'], 'prme_review_created_idx');
        });

        DB::table('product_reviews')
            ->whereNotNull('user_id')
            ->where('is_verified_purchase', true)
            ->whereBetween('rating', [1, 2])
            ->whereIn('status', ['pending', 'published'])
            ->orderBy('id')
            ->chunkById(500, function ($reviews): void {
                foreach ($reviews as $review) {
                    $recordedAt = $review->created_at ?: now();
                    DB::table('product_review_negative_events')->insertOrIgnore([
                        'product_review_id' => $review->id,
                        'reviewer_user_id' => $review->user_id,
                        'product_id' => $review->product_id,
                        'rating' => $review->rating,
                        'event_type' => 'submitted',
                        'recorded_at' => $recordedAt,
                        'created_at' => $recordedAt,
                        'updated_at' => $recordedAt,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_review_moderation_events');
        Schema::dropIfExists('product_review_reports');
        Schema::dropIfExists('product_review_negative_events');
    }
};
