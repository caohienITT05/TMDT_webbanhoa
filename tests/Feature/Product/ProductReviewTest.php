<?php

namespace Tests\Feature\Product;

use App\Models\OrderItem;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesBloomGiftData;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use CreatesBloomGiftData, RefreshDatabase {
        CreatesBloomGiftData::afterRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_guest_can_read_existing_reviews_but_must_sign_in_before_reviewing(): void
    {
        $product = $this->createProduct();
        $legacyReview = ProductReview::create([
            'product_id' => $product->id,
            'reviewer_name' => 'Khách trước đây',
            'reviewer_email' => 'legacy@example.test',
            'rating' => 5,
            'content' => 'Đánh giá lịch sử vẫn được giữ nguyên và hiển thị công khai.',
            'status' => 'published',
            'published_at' => now(),
            'is_verified_purchase' => false,
        ]);

        $this->get(route('product.detail', $product->slug))
            ->assertOk()
            ->assertSee('Đánh giá lịch sử vẫn được giữ nguyên')
            ->assertSee('Vui lòng đăng nhập để đánh giá sản phẩm.')
            ->assertSee('Đánh giá sản phẩm')
            ->assertSee(route('login'))
            ->assertDontSee('name="reviewer_email"');

        $this->post(route('products.reviews.store', $product), [
            'rating' => 5,
            'content' => 'Guest không được phép gửi đánh giá.',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('product_reviews', 1);
        $this->assertDatabaseHas('product_reviews', ['id' => $legacyReview->id, 'status' => 'published']);
    }

    public function test_signed_in_customer_can_review_without_buying_the_product(): void
    {
        $customer = $this->createCustomer();
        $product = $this->createProduct();

        $this->actingAs($customer)->get(route('product.detail', $product->slug))
            ->assertOk()
            ->assertSee('name="rating"', false)
            ->assertSee('Đã mua hàng” chỉ hiển thị khi đơn hàng được xác nhận.');

        $response = $this->actingAs($customer)->post(route('products.reviews.store', $product), [
            'rating' => 5,
            'content' => 'Tôi chưa mua sản phẩm này.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'user_id' => $customer->id,
            'order_id' => null,
            'is_verified_purchase' => false,
            'status' => 'published',
        ]);
    }

    public function test_customer_review_is_not_attached_to_another_customers_order(): void
    {
        $customer = $this->createCustomer(['name' => 'Khách Đúng', 'email' => 'buyer@example.test']);
        $otherCustomer = $this->createCustomer(['email' => 'other@example.test']);
        $product = $this->createProduct();

        $otherOrder = $this->createOrder($otherCustomer, [
            'order_status' => 'completed', 'status' => 'COMPLETED', 'payment_status' => 'paid',
        ]);
        OrderItem::create([
            'order_id' => $otherOrder->id, 'product_id' => $product->id, 'product_name' => $product->name,
            'price' => 350000, 'quantity' => 1, 'subtotal' => 350000,
        ]);
        $this->actingAs($customer)->post(route('products.reviews.store', $product), [
            'rating' => 4, 'content' => 'Đánh giá cho đơn hàng của người khác.',
            'reviewer_name' => 'Tên giả do người gửi nhập',
            'reviewer_email' => 'forged@example.test',
            'order_code' => $otherOrder->order_code,
            'user_id' => $otherCustomer->id,
            'status' => 'published',
            'is_verified_purchase' => false,
        ])->assertRedirect();

        $review = ProductReview::query()->firstOrFail();
        $this->assertSame($customer->id, $review->user_id);
        $this->assertNull($review->order_id);
        $this->assertSame('Khách Đúng', $review->reviewer_name);
        $this->assertSame('buyer@example.test', $review->reviewer_email);
        $this->assertFalse($review->is_verified_purchase);
        $this->assertSame('published', $review->status);
    }

    public function test_incomplete_order_does_not_verify_review_and_duplicate_customer_review_is_rejected(): void
    {
        $customer = $this->createCustomer();
        $product = $this->createProduct();
        $order = $this->createOrder($customer, ['order_status' => 'confirmed', 'status' => 'CONFIRMED']);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name,
            'price' => 350000, 'quantity' => 1, 'subtotal' => 350000,
        ]);

        $this->actingAs($customer)->post(route('products.reviews.store', $product), [
            'rating' => 5, 'content' => 'Đơn này chưa được giao hoàn tất.',
        ])->assertRedirect();

        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'user_id' => $customer->id,
            'order_id' => null,
            'is_verified_purchase' => false,
        ]);

        $order->update(['order_status' => 'delivered', 'status' => 'DELIVERED']);
        $payload = ['rating' => 5, 'content' => 'Sản phẩm đẹp và được giao đúng hẹn.'];
        $this->actingAs($customer)->post(route('products.reviews.store', $product), $payload)->assertRedirect();
        $this->actingAs($customer)->post(route('products.reviews.store', $product), $payload)->assertSessionHasErrors('rating');
        $this->assertDatabaseCount('product_reviews', 1);
    }

    public function test_customer_can_edit_their_review_with_a_revision_and_negative_history(): void
    {
        $customer = $this->createCustomer();
        $product = $this->createProduct();

        $this->actingAs($customer)->post(route('products.reviews.store', $product), [
            'rating' => 5,
            'content' => 'Sản phẩm ban đầu đẹp và được đóng gói cẩn thận.',
        ])->assertRedirect();

        $review = ProductReview::query()->where('product_id', $product->id)->firstOrFail();
        $this->actingAs($customer)->get(route('product.detail', $product->slug))
            ->assertOk()
            ->assertSee('Chỉnh sửa đánh giá')
            ->assertSee('Lưu chỉnh sửa');

        $this->actingAs($customer)->put(route('products.reviews.update', $product), [
            'rating' => 1,
            'content' => 'Hoa bị dập khi nhận hàng và tôi chưa hài lòng.',
        ])->assertRedirect();

        $this->assertSame(1, $review->fresh()->rating);
        $this->assertSame('published', $review->fresh()->status);
        $this->assertDatabaseCount('product_review_negative_events', 1);

        $this->actingAs($customer)->put(route('products.reviews.update', $product), [
            'rating' => 5,
            'content' => 'Cửa hàng đã hỗ trợ đổi hoa và xử lý rất nhanh.',
        ])->assertRedirect();

        $this->assertSame(5, $review->fresh()->rating);
        $this->assertDatabaseCount('product_reviews', 1);
        $this->assertDatabaseCount('product_review_negative_events', 1);
        $this->assertDatabaseCount('product_review_revisions', 2);
        $this->assertDatabaseHas('product_review_revisions', [
            'product_review_id' => $review->id,
            'previous_rating' => 5,
            'new_rating' => 1,
            'previous_content' => 'Sản phẩm ban đầu đẹp và được đóng gói cẩn thận.',
            'new_status' => 'published',
        ]);

        $otherCustomer = $this->createCustomer();
        $this->actingAs($otherCustomer)->put(route('products.reviews.update', $product), [
            'rating' => 4,
            'content' => 'Không được sửa đánh giá của tài khoản khác.',
        ])->assertNotFound();
        $this->assertSame(2, DB::table('product_review_revisions')->count());
    }

    public function test_admin_cannot_submit_a_customer_product_review(): void
    {
        $admin = $this->createAdmin();
        $product = $this->createProduct();

        $this->actingAs($admin)->post(route('products.reviews.store', $product), [
            'rating' => 5, 'content' => 'Admin không thể giả danh khách mua hàng.',
        ])->assertForbidden();
        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_published_rating_filter_and_moderation_remain_working(): void
    {
        $product = $this->createProduct();
        $review = ProductReview::create([
            'product_id' => $product->id,
            'reviewer_name' => 'Khách mua',
            'reviewer_email' => 'published@example.test',
            'rating' => 5,
            'content' => 'Sản phẩm tươi, giao đúng hẹn và đóng gói đẹp.',
            'status' => 'pending',
            'is_verified_purchase' => true,
        ]);

        $this->get(route('product.detail', $product->slug))->assertOk()->assertDontSee('Sản phẩm tươi, giao đúng hẹn');
        $this->get(route('products.index', ['min_rating' => 4]))->assertOk()->assertDontSee($product->name);
        $this->actingAs($this->createAdmin())->put(route('admin.reviews.update', $review), [
            'status' => 'published', 'store_response' => 'Cảm ơn bạn đã chọn BloomGift.',
        ])->assertSessionHasNoErrors();

        $review->refresh();
        $this->assertNotNull($review->published_at);
        $this->get(route('product.detail', $product->slug))->assertOk()->assertSee('Sản phẩm tươi, giao đúng hẹn');
        $this->get(route('products.index', ['sort' => 'top_rated']))->assertOk()->assertSee($product->name);
        $this->get(route('products.index', ['min_rating' => 4]))->assertOk()->assertSee($product->name);
    }

    public function test_three_to_five_star_reviews_publish_immediately_and_json_returns_public_rating(): void
    {
        Storage::fake('public');
        $customer = $this->createCustomer();
        Carbon::setTestNow(now());
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/iZkAAAAASUVORK5CYII=');

        foreach ([3, 4, 5] as $index => $rating) {
            if ($index > 0) $this->travel(61)->seconds();
            $product = $this->createProduct();
            $this->addPurchase($customer, $product);
            $response = $this->actingAs($customer)->postJson(route('products.reviews.store', $product), [
                'rating' => $rating,
                'content' => "Đánh giá {$rating} sao, hoa đẹp và giao đúng lịch.",
                'image' => UploadedFile::fake()->createWithContent('review.png', $png),
            ]);

            $response->assertCreated()->assertJsonPath('needs_review', false)->assertJsonPath('review.rating', $rating);
            $this->assertDatabaseHas('product_reviews', ['product_id' => $product->id, 'rating' => $rating, 'status' => 'published', 'is_verified_purchase' => true]);
        }

        $review = ProductReview::query()->firstOrFail();
        $this->assertNotEmpty($review->image_path);
        Storage::disk('public')->assertExists($review->image_path);
        Carbon::setTestNow();
    }

    public function test_customer_review_form_requires_only_stars_and_content_with_optional_image(): void
    {
        $customer = $this->createCustomer();
        $product = $this->createProduct();
        $this->addPurchase($customer, $product);

        $this->actingAs($customer)->get(route('product.detail', $product->slug))
            ->assertOk()
            ->assertSee('role="radiogroup"', false)
            ->assertSee('Nội dung đánh giá')
            ->assertSee('Thêm hình ảnh (không bắt buộc')
            ->assertSee('Gửi đánh giá')
            ->assertDontSee('name="reviewer_name"', false)
            ->assertDontSee('name="reviewer_email"', false)
            ->assertDontSee('name="order_id"', false);
    }

    public function test_invalid_review_image_is_rejected_before_creating_review_or_history(): void
    {
        Storage::fake('public');
        $customer = $this->createCustomer();
        $product = $this->createProduct();
        $this->addPurchase($customer, $product);

        $this->actingAs($customer)->postJson(route('products.reviews.store', $product), [
            'rating' => 5,
            'content' => 'Hoa đẹp và giao đúng lịch hẹn.',
            'image' => UploadedFile::fake()->create('not-an-image.txt', 2, 'text/plain'),
        ])->assertUnprocessable()->assertJsonValidationErrors('image');

        $this->assertDatabaseCount('product_reviews', 0);
        $this->assertDatabaseCount('product_review_negative_events', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('product-reviews'));
    }

    public function test_retrying_the_same_negative_review_does_not_duplicate_review_or_counter_event(): void
    {
        $customer = $this->createCustomer();
        $product = $this->createProduct();
        $this->addPurchase($customer, $product);
        $payload = ['rating' => 1, 'content' => 'Sản phẩm chưa đúng như mô tả.'];

        $this->actingAs($customer)->postJson(route('products.reviews.store', $product), $payload)
            ->assertCreated()->assertJsonPath('needs_review', false);
        $this->actingAs($customer)->postJson(route('products.reviews.store', $product), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('rating');

        $this->assertDatabaseCount('product_reviews', 1);
        $this->assertDatabaseCount('product_review_negative_events', 1);
    }

    public function test_four_star_filter_includes_five_four_point_five_and_four_but_excludes_lower_and_pending(): void
    {
        $makeReview = function ($product, int $rating, string $email, string $status = 'published'): void {
            ProductReview::create([
                'product_id' => $product->id,
                'reviewer_name' => 'Khách mua',
                'reviewer_email' => $email,
                'rating' => $rating,
                'content' => 'Đánh giá đã được ghi nhận theo trải nghiệm mua hàng.',
                'status' => $status,
                'published_at' => $status === 'published' ? now() : null,
                'is_verified_purchase' => true,
            ]);
        };

        $five = $this->createProduct(['name' => 'Hoa 5 sao', 'slug' => 'hoa-5-sao']);
        $fourPointFive = $this->createProduct(['name' => 'Hoa 4,5 sao', 'slug' => 'hoa-45-sao']);
        $four = $this->createProduct(['name' => 'Hoa 4 sao', 'slug' => 'hoa-4-sao']);
        $belowFour = $this->createProduct(['name' => 'Hoa dưới 4 sao', 'slug' => 'hoa-duoi-4-sao']);
        $pending = $this->createProduct(['name' => 'Hoa đang chờ', 'slug' => 'hoa-dang-cho']);

        $makeReview($five, 5, 'five@example.test');
        $makeReview($fourPointFive, 5, 'four-half-a@example.test');
        $makeReview($fourPointFive, 4, 'four-half-b@example.test');
        $makeReview($four, 4, 'four@example.test');
        $makeReview($belowFour, 4, 'below-a@example.test');
        $makeReview($belowFour, 3, 'below-b@example.test');
        $makeReview($pending, 5, 'pending@example.test', 'pending');

        $this->get(route('products.index', ['min_rating' => 4]))
            ->assertOk()
            ->assertSee($five->name)
            ->assertSee($fourPointFive->name)
            ->assertSee($four->name)
            ->assertDontSee($belowFour->name)
            ->assertDontSee($pending->name);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee($pending->name);
    }

    public function test_first_ten_negative_reviews_publish_and_eleventh_waits_for_admin(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $customer = $this->createCustomer();
        $reviews = [];

        for ($number = 1; $number <= 10; $number++) {
            $this->travel(61)->seconds();
            $product = $this->createProduct();
            $this->addPurchase($customer, $product);
            $this->actingAs($customer)->post(route('products.reviews.store', $product), [
                'rating' => 1,
                'content' => "Đánh giá tiêu cực hợp lệ số {$number}.",
            ])->assertRedirect();
            $reviews[] = ProductReview::query()->where('product_id', $product->id)->firstOrFail();
        }

        for ($number = 1; $number <= 20; $number++) {
            $product = $this->createProduct();
            $this->addPurchase($customer, $product);
            $this->actingAs($customer)->post(route('products.reviews.store', $product), [
                'rating' => 5,
                'content' => "Đánh giá tích cực thứ {$number} sau ngưỡng tiêu cực.",
            ])->assertRedirect();
            $this->assertDatabaseHas('product_reviews', ['product_id' => $product->id, 'status' => 'published', 'rating' => 5]);
        }

        $this->travel(61)->seconds();
        $eleventhNegativeProduct = $this->createProduct();
        $this->addPurchase($customer, $eleventhNegativeProduct);
        $this->actingAs($customer)->post(route('products.reviews.store', $eleventhNegativeProduct), [
            'rating' => 1,
            'content' => 'Đánh giá tiêu cực thứ mười một sau nhiều đánh giá tốt.',
        ])->assertRedirect();
        $reviews[] = ProductReview::query()->where('product_id', $eleventhNegativeProduct->id)->firstOrFail();

        foreach (array_slice($reviews, 0, 10) as $review) {
            $this->assertSame('published', $review->status);
            $this->assertNotNull($review->published_at);
        }
        $this->assertSame('pending', $reviews[10]->status);
        $this->assertNull($reviews[10]->published_at);
        $this->assertDatabaseCount('product_review_negative_events', 11);

        $positiveProduct = $this->createProduct();
        $this->addPurchase($customer, $positiveProduct);
        $this->actingAs($customer)->post(route('products.reviews.store', $positiveProduct), [
            'rating' => 5,
            'content' => 'Đánh giá tích cực vẫn được đăng ngay sau ngưỡng.',
        ])->assertRedirect();
        $this->assertDatabaseHas('product_reviews', ['product_id' => $positiveProduct->id, 'status' => 'published', 'rating' => 5]);
        $this->assertDatabaseCount('product_review_negative_events', 11);
    }

    public function test_admin_publishing_the_eleventh_negative_review_does_not_reset_counter_or_publish_the_next_one(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $customer = $this->createCustomer();
        $reviews = [];

        for ($number = 1; $number <= 11; $number++) {
            $this->travel(61)->seconds();
            $product = $this->createProduct();
            $this->addPurchase($customer, $product);
            $this->actingAs($customer)->post(route('products.reviews.store', $product), [
                'rating' => 2,
                'content' => "Đánh giá tiêu cực hợp lệ thứ {$number}.",
            ])->assertRedirect();
            $reviews[] = ProductReview::query()->where('product_id', $product->id)->firstOrFail();
        }

        $this->assertSame('pending', $reviews[10]->status);
        $this->actingAs($this->createAdmin())->put(route('admin.reviews.update', $reviews[10]), [
            'status' => 'published',
            'moderation_note' => 'Đã xem xét và công bố đánh giá hợp lệ.',
        ])->assertRedirect();
        $this->assertDatabaseHas('product_review_negative_events', ['product_review_id' => $reviews[10]->id, 'reviewer_user_id' => $customer->id]);

        $nextProduct = $this->createProduct();
        $this->travel(61)->seconds();
        $this->addPurchase($customer, $nextProduct);
        $this->actingAs($customer)->post(route('products.reviews.store', $nextProduct), [
            'rating' => 1,
            'content' => 'Đánh giá tiêu cực thứ mười hai vẫn chờ xem xét.',
        ])->assertRedirect();

        $this->assertDatabaseHas('product_reviews', ['product_id' => $nextProduct->id, 'status' => 'pending']);
        $this->assertDatabaseCount('product_review_negative_events', 12);
    }

    public function test_interleaved_positive_reviews_do_not_reset_lifetime_negative_count(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $customer = $this->createCustomer();
        $ratings = [1, 2, 1, 2, 1, 2, 1, 2, 5, 1, 2, 1];
        $lastReview = null;

        foreach ($ratings as $index => $rating) {
            $this->travel(61)->seconds();
            $product = $this->createProduct();
            $this->addPurchase($customer, $product);
            $this->actingAs($customer)->post(route('products.reviews.store', $product), [
                'rating' => $rating,
                'content' => 'Trải nghiệm khách hàng được ghi theo lịch sử tài khoản.',
            ])->assertRedirect();
            $lastReview = ProductReview::query()->where('product_id', $product->id)->firstOrFail();
            if ($index < 11) {
                $this->assertSame('published', $lastReview->status);
            }
        }

        $this->assertSame('pending', $lastReview->status);
        $this->assertDatabaseCount('product_review_negative_events', 11);
    }

    public function test_deleting_a_negative_review_does_not_reset_the_audit_counter(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $customer = $this->createCustomer();
        $negativeReviews = [];
        for ($number = 1; $number <= 10; $number++) {
            $this->travel(61)->seconds();
            $product = $this->createProduct();
            $this->addPurchase($customer, $product);
            $this->actingAs($customer)->post(route('products.reviews.store', $product), [
                'rating' => 2,
                'content' => 'Đánh giá tiêu cực hợp lệ cần được lưu lịch sử.',
            ])->assertRedirect();
            $negativeReviews[] = ProductReview::query()->where('product_id', $product->id)->firstOrFail();
        }

        $negativeReviews[0]->delete();
        $this->assertDatabaseHas('product_review_negative_events', ['product_review_id' => null, 'reviewer_user_id' => $customer->id]);

        $product = $this->createProduct();
        $this->travel(61)->seconds();
        $this->addPurchase($customer, $product);
        $this->actingAs($customer)->post(route('products.reviews.store', $product), [
            'rating' => 1,
            'content' => 'Đánh giá tiêu cực thứ mười một vẫn chờ xem xét.',
        ])->assertRedirect();
        $this->assertDatabaseHas('product_reviews', ['product_id' => $product->id, 'status' => 'pending']);
        $this->assertDatabaseCount('product_review_negative_events', 11);
    }

    public function test_reported_review_can_be_hidden_and_moderation_history_is_kept(): void
    {
        $author = $this->createCustomer();
        $reporter = $this->createCustomer();
        $product = $this->createProduct();
        [$order] = $this->addPurchase($author, $product);
        $review = ProductReview::create([
            'product_id' => $product->id,
            'user_id' => $author->id,
            'order_id' => $order->id,
            'reviewer_name' => $author->name,
            'reviewer_email' => $author->email,
            'rating' => 2,
            'content' => 'Đánh giá này cần được kiểm tra theo báo cáo.',
            'status' => 'published',
            'published_at' => now(),
            'is_verified_purchase' => true,
        ]);

        $this->actingAs($reporter)->post(route('products.reviews.report', $review), [
            'reason' => 'spam',
            'details' => 'Nội dung lặp lại không liên quan.',
        ])->assertRedirect();
        $this->actingAs($this->createAdmin())->get(route('admin.reviews.index', ['status' => 'reported']))
            ->assertOk()->assertSee('Bị báo cáo')->assertSee('Spam')->assertSee($review->content);

        $this->put(route('admin.reviews.update', $review), [
            'status' => 'hidden',
            'moderation_note' => 'Đã kiểm tra báo cáo',
        ])->assertRedirect();
        $this->assertDatabaseHas('product_reviews', ['id' => $review->id, 'status' => 'hidden', 'published_at' => null]);
        $this->assertDatabaseHas('product_review_reports', ['product_review_id' => $review->id, 'resolved_by' => auth()->id()]);
        $this->assertDatabaseHas('product_review_moderation_events', ['product_review_id' => $review->id, 'to_status' => 'hidden', 'note' => 'Đã kiểm tra báo cáo']);
        $this->get(route('admin.reviews.index', ['status' => 'hidden']))->assertOk()->assertSee('Lịch sử xử lý');
    }

    private function addPurchase(User $customer, $product): array
    {
        $order = $this->createOrder($customer, [
            'order_status' => 'completed',
            'status' => 'COMPLETED',
            'payment_status' => 'paid',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 350000,
            'quantity' => 1,
            'subtotal' => 350000,
        ]);

        return [$order, $product];
    }
}
