<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ProductReviewController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['status' => ['nullable', Rule::in(['all', 'published', 'needs-review', 'reported', 'hidden'])]]);
        $status = $data['status'] ?? 'all';
        $reportsAvailable = Schema::hasTable('product_review_reports');
        $historyAvailable = Schema::hasTable('product_review_negative_events');

        $base = ProductReview::query();
        $statusCounts = [
            'all' => (clone $base)->count(),
            'published' => (clone $base)->where('status', 'published')->count(),
            'needs-review' => (clone $base)->where('status', 'pending')->count(),
            'reported' => $reportsAvailable
                ? (clone $base)->whereHas('reports', fn ($query) => $query->whereNull('resolved_at'))->count()
                : 0,
            'hidden' => (clone $base)->where('status', 'hidden')->count(),
        ];

        $reviewsQuery = ProductReview::query()->with(['product', 'order'])
            ->when($status === 'published', fn ($query) => $query->where('status', 'published'))
            ->when($status === 'needs-review', fn ($query) => $query->where('status', 'pending'))
            ->when($status === 'hidden', fn ($query) => $query->where('status', 'hidden'))
            ->when($status === 'reported' && $reportsAvailable, fn ($query) => $query->whereHas('reports', fn ($reports) => $reports->whereNull('resolved_at')))
            ->when($status === 'reported' && ! $reportsAvailable, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($reportsAvailable, fn ($query) => $query->with(['reports' => fn ($reports) => $reports->whereNull('resolved_at')->latest()]))
            ->latest();

        $reviews = $reviewsQuery->paginate(20)->withQueryString();
        $negativeCounts = collect();
        if ($historyAvailable) {
            $userIds = $reviews->getCollection()->pluck('user_id')->filter()->unique()->values();
            if ($userIds->isNotEmpty()) {
                $negativeCounts = DB::table('product_review_negative_events')
                    ->select('reviewer_user_id', DB::raw('COUNT(*) as negative_count'))
                    ->whereIn('reviewer_user_id', $userIds)
                    ->groupBy('reviewer_user_id')
                    ->pluck('negative_count', 'reviewer_user_id');
            }
        }
        foreach ($reviews as $review) {
            $review->lifetime_negative_count = (int) ($negativeCounts[$review->user_id] ?? 0);
        }

        $moderationHistory = collect();
        if (Schema::hasTable('product_review_moderation_events') && $reviews->isNotEmpty()) {
            $moderationHistory = DB::table('product_review_moderation_events')
                ->whereIn('product_review_id', $reviews->getCollection()->pluck('id'))
                ->latest('created_at')
                ->get()
                ->groupBy('product_review_id');
        }

        return view('admin.reviews', compact('reviews', 'status', 'statusCounts', 'reportsAvailable', 'historyAvailable', 'moderationHistory'));
    }

    public function update(Request $request, ProductReview $review)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['published', 'hidden', 'rejected'])],
            'store_response' => ['nullable', 'string', 'max:2000'],
            'moderation_note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $review, $data): void {
            $review = ProductReview::query()->lockForUpdate()->findOrFail($review->id);
            $previousStatus = (string) $review->status;
            $newStatus = $data['status'];

            if ($previousStatus !== $newStatus && Schema::hasTable('product_review_moderation_events')) {
                DB::table('product_review_moderation_events')->insert([
                    'product_review_id' => $review->id,
                    'moderator_user_id' => $request->user()->id,
                    'from_status' => $previousStatus,
                    'to_status' => $newStatus,
                    'note' => $data['moderation_note'] ?? null,
                    'created_at' => now(),
                ]);
            }

            $review->update([
                'status' => $newStatus,
                'store_response' => array_key_exists('store_response', $data) ? $data['store_response'] : $review->store_response,
                'published_at' => $newStatus === 'published' ? ($review->published_at ?? now()) : null,
            ]);

            if (Schema::hasTable('product_review_reports')) {
                DB::table('product_review_reports')->where('product_review_id', $review->id)->whereNull('resolved_at')->update([
                    'resolved_at' => now(),
                    'resolved_by' => $request->user()->id,
                    'updated_at' => now(),
                ]);
            }
        });

        $message = match ($data['status']) {
            'published' => 'Đánh giá đã được công bố.',
            'hidden' => 'Đánh giá đã được ẩn; lịch sử vẫn được giữ lại.',
            default => 'Đánh giá đã được từ chối; lịch sử vẫn được giữ lại.',
        };

        return back()->with('success', $message);
    }
}
