<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomOrderRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomOrderController extends Controller
{
    public function create(Request $request): View
    {
        return view('Customer.custom-order', ['customer' => $request->user()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'occasion' => ['nullable', 'string', 'max:255'],
            'flower_type' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:today'],
            'delivery_address' => ['nullable', 'string', 'max:1000'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $customOrderRequest = CustomOrderRequest::create([
            ...$validated,
            'request_code' => $this->nextRequestCode(),
            'user_id' => $request->user()?->id,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('custom.order')
            ->with('success', 'BloomGift đã nhận yêu cầu của bạn. Chủ cửa hàng sẽ liên hệ để trao đổi và xác nhận.')
            ->with('custom_order_request_code', $customOrderRequest->request_code);
    }

    public function index(Request $request): View
    {
        $customOrderRequests = CustomOrderRequest::query()
            ->where('user_id', $request->user()->id)
            ->with('order')
            ->latest()
            ->paginate(10);

        return view('Customer.custom-orders.index', compact('customOrderRequests'));
    }

    public function show(Request $request, CustomOrderRequest $customOrderRequest): View
    {
        abort_unless((int) $customOrderRequest->user_id === (int) $request->user()->id, 403);

        $customOrderRequest->load('order');

        return view('Customer.custom-orders.show', compact('customOrderRequest'));
    }

    private function nextRequestCode(): string
    {
        $sequence = (int) CustomOrderRequest::query()->max('id') + 1;

        do {
            $code = 'YC' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $sequence++;
        } while (CustomOrderRequest::query()->where('request_code', $code)->exists());

        return $code;
    }
}
