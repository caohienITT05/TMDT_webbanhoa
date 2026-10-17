<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\FinanceAnalyticsService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Danh sách đơn hàng.
     */
    public function index()
    {
        $orders = Order::with([
            'deliverySlot',
            'payment',
            'user',
        ])
            ->latest()
            ->paginate(10);

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Chi tiết đơn hàng.
     */
    public function show(Order $order)
    {
        // Tải các quan hệ (hỗ trợ cả orderItems lẫn items)
        $relations = ['deliverySlot', 'payment', 'user'];
        if (method_exists($order, 'orderItems')) {
            $relations[] = 'orderItems.product';
        }
        if (method_exists($order, 'items')) {
            $relations[] = 'items';
        }

        $order->load($relations);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Cập nhật trạng thái đơn hàng và đồng bộ doanh thu.
     */
    public function updateStatus(Request $request, Order $order, FinanceAnalyticsService $finance, InventoryService $inventory)
    {
        $oldStatus = strtolower((string) ($order->order_status ?: $order->status));
        // Chấp nhận cả giá trị gửi lên từ trường status hoặc order_status
        $inputStatus = $request->input('status') ?? $request->input('order_status');

        $request->merge(['order_status' => strtolower($inputStatus)]);

        $validated = $request->validate([
            'order_status' => [
                'required',
                'in:pending,confirmed,preparing,processing,shipping,completed,cancelled',
            ],
        ]);

        $statusLower = strtolower($validated['order_status']);
        $statusUpper = strtoupper($statusLower);

        if (in_array($statusLower, ['completed', 'delivered'], true)
            && strtolower((string) $order->payment_method) === 'paypal'
            && strtolower((string) $order->payment_status) !== 'paid') {
            return redirect()->back()->with(
                'error',
                'Không thể hoàn tất đơn PayPal khi PayPal chưa xác nhận Capture thành công.'
            );
        }

        // Chuẩn bị dữ liệu cập nhật đồng bộ cả 2 cột
        $updateData = [
            'order_status' => $statusLower,
            'status' => $statusUpper,
        ];

        // COD được xác nhận đã thu khi đơn hoàn tất. PayPal chỉ được đánh dấu
        // paid bởi callback đã đối chiếu Capture COMPLETED.
        if (in_array($statusLower, ['completed', 'delivered'])) {
            if (strtolower((string) $order->payment_method) !== 'paypal') {
                $updateData['payment_status'] = 'paid';

                // Cập nhật bản ghi giao dịch trong bảng payments nếu có
                Payment::where('order_id', $order->id)->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                ]);
            }
        }

        DB::transaction(function () use ($order, $updateData, $statusLower, $oldStatus, $inventory): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $lockedOrder->update($updateData);

            if ($statusLower === 'cancelled' && $oldStatus !== 'cancelled') {
                $inventory->releaseForCancellation($lockedOrder->fresh(), $oldStatus);
            }
        });
        $order->refresh();

        if ($oldStatus !== 'completed' && $statusLower === 'completed') {
            $finance->finalizeOrderFinancials($order->fresh());
        }

        if ($oldStatus !== $statusLower) {
            Log::channel('transactions')->info('order_status_changed', [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'user_id' => $order->user_id,
                'payment_method' => $order->payment_method,
                'amount' => (float) $order->total,
                'from_status' => $oldStatus,
                'to_status' => $statusLower,
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Cập nhật trạng thái đơn hàng hoa #' . ($order->order_code ?? $order->id) . ' thành công.');
    }
}
