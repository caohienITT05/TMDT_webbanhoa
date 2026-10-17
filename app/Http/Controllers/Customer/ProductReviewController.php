<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $userId = (int) $request->user()->id;
        abort_unless($request->user()->role === 'customer' && $request->user()->is_active, 403);
        abort_unless($product->is_active, 404);
        abort_unless(Schema::hasTable('product_reviews') && Schema::hasTable('product_review_negative_events'), 503, 'Hệ thống đánh giá đang được cập nhật. Vui lòng thử lại sau.');

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'content' => ['required', 'string', 'min:10', 'max:4000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'rating.required' => 'Vui lòng chọn số sao.',
            'rating.between' => 'Số sao phải từ 1 đến 5.',
            'content.required' => 'Vui lòng nhập nội dung đánh giá.',
            'content.min' => 'Nội dung cần ít nhất 10 ký tự.',
            'image.image' => 'Tệp tải lên phải là ảnh hợp lệ.',
            'image.mimes' => 'Ảnh chỉ nhận JPG, PNG hoặc WebP.',
            'image.max' => 'Ảnh không được vượt quá 4 MB.',
        ]);

        $email = mb_strtolower(trim((string) $request->user()->email));
        $imagePath = $request->file('image')?->store('product-reviews', 'public');

        try {
            [$review, $negativeReviewNumber, $needsReview] = DB::transaction(function () use ($data, $email, $imagePath, $product, $userId): array {
                // Serializing on the customer row makes the lifetime negative-review
                // threshold atomic even when two different products are submitted at once.
                $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();
                abort_unless($user->role === 'customer' && $user->is_active, 403);

                $duplicate = ProductReview::query()->where('product_id', $product->id)
                    ->where(function ($query) use ($user, $email): void {
                        $query->where('user_id', $user->id)->orWhereRaw('LOWER(reviewer_email) = ?', [$email]);
                    })->exists();
                if ($duplicate) {
                    throw ValidationException::withMessages(['rating' => 'Bạn đã gửi đánh giá cho sản phẩm này.']);
                }

                $order = $user->orders()->completedOrDelivered()
                    ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
                    ->latest('id')
                    ->first();

                $rating = (int) $data['rating'];
                $negativeCount = DB::table('product_review_negative_events')
                    ->where('reviewer_user_id', $user->id)
                    ->count();
                $needsReview = $rating <= 2 && $negativeCount >= 10;
                $review = ProductReview::create([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'order_id' => $order?->id,
                    'reviewer_name' => $user->name,
                    'reviewer_email' => $email,
                    'rating' => $rating,
                    'content' => $data['content'],
                    'image_path' => $imagePath,
                    'status' => $needsReview ? 'pending' : 'published',
                    'is_verified_purchase' => (bool) $order,
                    'published_at' => $needsReview ? null : now(),
                ]);

                if ($rating <= 2) {
                    DB::table('product_review_negative_events')->insert([
                        'product_review_id' => $review->id,
                        'reviewer_user_id' => $user->id,
                        'product_id' => $product->id,
                        'rating' => $rating,
                        'event_type' => 'submitted',
                        'recorded_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $negativeCount++;
                }

                return [$review, $negativeCount, $needsReview];
            }, 3);
        } catch (QueryException $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            if (in_array((string) ($exception->errorInfo[0] ?? ''), ['23000', '23505'], true) || (int) ($exception->errorInfo[1] ?? 0) === 19) {
                throw ValidationException::withMessages(['rating' => 'Bạn đã gửi đánh giá cho sản phẩm này.']);
            }
            throw $exception;
        } catch (\Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            throw $exception;
        }

        $message = $needsReview
            ? 'Đánh giá đã được gửi và đang chờ cửa hàng xem xét.'
            : 'Cảm ơn bạn! Đánh giá của bạn đã được đăng.';

        if ($request->expectsJson()) {
            $summary = DB::table('product_reviews')->where('product_id', $product->id)
                ->where('status', 'published')->whereNotNull('published_at')
                ->selectRaw('COUNT(*) as review_count, AVG(rating) as average_rating')->first();

            return response()->json([
                'message' => $message,
                'needs_review' => $needsReview,
                'negative_review_number' => $negativeReviewNumber,
                'review' => $needsReview ? null : [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'content' => $review->content,
                    'reviewer_name' => $review->reviewer_name,
                    'published_at' => $review->published_at?->format('d/m/Y'),
                    'image_url' => $review->image_path ? Storage::disk('public')->url($review->image_path) : null,
                ],
                'summary' => [
                    'count' => (int) ($summary->review_count ?? 0),
                    'average' => $summary->average_rating === null ? null : round((float) $summary->average_rating, 1),
                ],
            ], 201);
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, Product $product)
    {
        $user = $request->user();
        abort_unless($user && $user->role === 'customer' && $user->is_active, 403);
        abort_unless($product->is_active, 404);
        abort_unless(
            Schema::hasTable('product_reviews')
                && Schema::hasTable('product_review_negative_events')
                && Schema::hasTable('product_review_revisions'),
            503,
            'Chức năng chỉnh sửa đánh giá đang được cập nhật.'
        );

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'content' => ['required', 'string', 'min:10', 'max:4000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'rating.required' => 'Vui lòng chọn số sao.',
            'rating.between' => 'Số sao phải từ 1 đến 5.',
            'content.required' => 'Vui lòng nhập nội dung đánh giá.',
            'content.min' => 'Nội dung cần ít nhất 10 ký tự.',
            'image.image' => 'Tệp tải lên phải là ảnh hợp lệ.',
            'image.mimes' => 'Ảnh chỉ nhận JPG, PNG hoặc WebP.',
            'image.max' => 'Ảnh không được vượt quá 4 MB.',
        ]);

        $email = mb_strtolower(trim((string) $user->email));
        $newImagePath = $request->file('image')?->store('product-reviews', 'public');

        try {
            [$review, $negativeReviewNumber, $needsReview] = DB::transaction(function () use ($data, $email, $newImagePath, $product, $user): array {
                User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $review = ProductReview::query()->where('product_id', $product->id)
                    ->where(function ($query) use ($user, $email): void {
                        $query->where('user_id', $user->id)->orWhereRaw('LOWER(reviewer_email) = ?', [$email]);
                    })->lockForUpdate()->firstOrFail();

                abort_unless((int) $review->user_id === (int) $user->id, 403);

                $negativeCount = DB::table('product_review_negative_events')
                    ->where('reviewer_user_id', $user->id)
                    ->count();
                $hasNegativeEvent = DB::table('product_review_negative_events')
                    ->where('product_review_id', $review->id)
                    ->exists();
                $rating = (int) $data['rating'];
                $newStatus = $review->status;
                $needsReview = $review->status === 'pending';

                if ($review->status !== 'hidden' && $review->status !== 'rejected') {
                    if ($rating >= 3) {
                        $newStatus = 'published';
                        $needsReview = false;
                    } elseif (! $hasNegativeEvent) {
                        $needsReview = $negativeCount >= 10;
                        $newStatus = $needsReview ? 'pending' : 'published';
                    }
                }

                $newImage = $newImagePath ?: $review->image_path;
                $publishedAt = $newStatus === 'published' ? ($review->published_at ?? now()) : null;
                DB::table('product_review_revisions')->insert([
                    'product_review_id' => $review->id,
                    'reviewer_user_id' => $user->id,
                    'previous_rating' => $review->rating,
                    'previous_content' => $review->content,
                    'previous_image_path' => $review->image_path,
                    'new_rating' => $rating,
                    'new_content' => $data['content'],
                    'new_image_path' => $newImage,
                    'previous_status' => $review->status,
                    'new_status' => $newStatus,
                    'created_at' => now(),
                ]);

                $review->update([
                    'rating' => $rating,
                    'content' => $data['content'],
                    'image_path' => $newImage,
                    'status' => $newStatus,
                    'published_at' => $publishedAt,
                ]);

                if ($rating <= 2 && ! $hasNegativeEvent) {
                    DB::table('product_review_negative_events')->insert([
                        'product_review_id' => $review->id,
                        'reviewer_user_id' => $user->id,
                        'product_id' => $product->id,
                        'rating' => $rating,
                        'event_type' => 'edited_to_negative',
                        'recorded_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $negativeCount++;
                }

                return [$review->fresh(), $negativeCount, $needsReview];
            }, 3);
        } catch (\Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }
            throw $exception;
        }

        $message = $needsReview
            ? 'Đánh giá đã được cập nhật và đang chờ cửa hàng xem xét.'
            : 'Đánh giá của bạn đã được cập nhật.';

        if ($request->expectsJson()) {
            $summary = DB::table('product_reviews')->where('product_id', $product->id)
                ->where('status', 'published')->whereNotNull('published_at')
                ->selectRaw('COUNT(*) as review_count, AVG(rating) as average_rating')->first();

            return response()->json([
                'message' => $message,
                'needs_review' => $needsReview,
                'negative_review_number' => $negativeReviewNumber,
                'review' => $needsReview ? null : [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'content' => $review->content,
                    'reviewer_name' => $review->reviewer_name,
                    'published_at' => $review->published_at?->format('d/m/Y'),
                    'image_url' => $review->image_path ? Storage::disk('public')->url($review->image_path) : null,
                ],
                'summary' => [
                    'count' => (int) ($summary->review_count ?? 0),
                    'average' => $summary->average_rating === null ? null : round((float) $summary->average_rating, 1),
                ],
            ]);
        }

        return back()->with('success', $message);
    }

    public function report(Request $request, ProductReview $review)
    {
        $user = $request->user();
        abort_unless($user && $user->role === 'customer' && $user->is_active, 403);
        abort_unless($review->status === 'published' && $review->published_at, 404);
        abort_if($review->user_id === $user->id, 403);
        abort_unless(Schema::hasTable('product_review_reports'), 503, 'Chức năng báo cáo đánh giá đang được cập nhật.');

        $data = $request->validate([
            'reason' => ['required', Rule::in(['spam', 'abuse', 'irrelevant', 'other'])],
            'details' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            DB::table('product_review_reports')->insert([
                'product_review_id' => $review->id,
                'reporter_user_id' => $user->id,
                'reason' => $data['reason'],
                'details' => $data['details'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $exception) {
            if (in_array((string) ($exception->errorInfo[0] ?? ''), ['23000', '23505'], true) || (int) ($exception->errorInfo[1] ?? 0) === 19) {
                throw ValidationException::withMessages(['reason' => 'Bạn đã báo cáo đánh giá này rồi.']);
            }
            throw $exception;
        }

        return back()->with('success', 'Đã gửi báo cáo để cửa hàng xem xét.');
    }
}
