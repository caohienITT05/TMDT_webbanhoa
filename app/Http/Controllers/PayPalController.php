<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\PayPalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayPalController extends Controller
{
    protected PayPalService $paypal;

    public function __construct(PayPalService $paypal)
    {
        $this->paypal = $paypal;
    }

    /**
     * Chuyển khách hàng sang PayPal Sandbox.
     */
    public function create(Order $order)
    {
        // Chỉ cho phép thanh toán những đơn dùng PayPal
        if ($order->payment_method !== 'paypal') {
            return redirect()
                ->route('orders.show', $order)
                ->with(
                    'error',
                    'Đơn hàng này không sử dụng PayPal.'
                );
        }

        // Nếu đã thanh toán thì không tạo giao dịch mới
        if ($order->payment_status === 'paid') {
            return redirect()
                ->route('orders.show', $order)
                ->with(
                    'success',
                    'Đơn hàng đã được thanh toán.'
                );
        }

        // Quy đổi VND -> USD
        $exchangeRate = (float) config(
            'services.paypal.exchange_rate',
            26054
        );

        $paypalAmount = (float) $order->total / $exchangeRate;

        // URL PayPal trả khách về
        $returnUrl = route('paypal.success', $order);

        // URL khi khách hủy thanh toán
        $cancelUrl = route('paypal.cancel', $order);

        try {
            $paypalOrder = $this->paypal->createOrder(
                $order->order_code,
                $paypalAmount,
                $returnUrl,
                $cancelUrl
            );

            // Lưu PayPal Order ID tạm thời
            $payment = $order->payment;

            if ($payment) {
                $payment->update([
                    'transaction_id' => $paypalOrder['id'] ?? null,
                ]);
            }

            // Tìm link approve của PayPal
            foreach ($paypalOrder['links'] ?? [] as $link) {
                if (($link['rel'] ?? '') === 'approve') {
                    return redirect()->away($link['href']);
                }
            }

            throw new RuntimeException(
                'Không tìm thấy đường dẫn thanh toán PayPal.'
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('orders.show', $order)
                ->with(
                    'error',
                    'Không thể kết nối PayPal: ' .
                    $e->getMessage()
                );
        }
    }

    /**
     * PayPal gọi về đây sau khi khách thanh toán/approve.
     */
    public function success(Request $request, Order $order)
    {
        $paypalOrderId = $request->query('token');

        if (!$paypalOrderId) {
            return redirect()
                ->route('orders.show', $order)
                ->with(
                    'error',
                    'Không nhận được mã giao dịch PayPal.'
                );
        }

        try {
            // Capture giao dịch PayPal
            $capture = $this->paypal->captureOrder(
                $paypalOrderId
            );

            $captureStatus = $capture['status'] ?? null;

            // Kiểm tra trạng thái thanh toán
            if ($captureStatus !== 'COMPLETED') {
                return redirect()
                    ->route('orders.show', $order)
                    ->with(
                        'error',
                        'Thanh toán PayPal chưa hoàn tất.'
                    );
            }

            // Lấy transaction ID thực tế từ PayPal
            $transactionId =
                $capture['purchase_units'][0]['payments']['captures'][0]['id']
                ?? $paypalOrderId;

            // Cập nhật database
            DB::transaction(function () use (
                $order,
                $transactionId
            ) {
                $payment = $order->payment;

                if (!$payment) {
                    Payment::create([
                        'order_id' => $order->id,
                        'payment_method' => 'paypal',
                        'transaction_id' => $transactionId,
                        'amount' => $order->total,
                        'status' => 'completed',
                        'paid_at' => now(),
                    ]);
                } else {
                    $payment->update([
                        'transaction_id' => $transactionId,
                        'status' => 'completed',
                        'paid_at' => now(),
                    ]);
                }

                $order->update([
                    'payment_status' => 'paid',
                ]);
            });

            // Thanh toán thành công
            return redirect()
                ->route('orders.show', $order)
                ->with(
                    'success',
                    'Thanh toán PayPal thành công!'
                );

        }catch (\Throwable $e) {
            return redirect()
                    ->route('orders.show', $order)
                    ->with(
                         'error',
                         'Thanh toán PayPal thất bại: ' .
                          $e->getMessage()
                     );


        }
    }

    /**
     * Khách hủy thanh toán PayPal.
     */
    public function cancel(Order $order)
    {
        return redirect()
            ->route('orders.show', $order)
            ->with(
                'error',
                'Bạn đã hủy thanh toán PayPal.'
            );
    }
}
