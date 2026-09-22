<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\HolidayReminderMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MarketingController extends Controller
{
    /**
     * Hiển thị trang form tạo chiến dịch email dịp lễ
     */
    public function index()
    {
        // Đếm số lượng khách hàng hợp lệ sẽ nhận được mail
        $totalCustomers = User::whereNotNull('email')->where('email', '!=', '')->count();

        return view('admin.marketing.holiday', compact('totalCustomers'));
    }

    /**
     * Xử lý gửi thư hàng loạt khi Admin bấm nút Gửi
     */
    public function sendHolidayEmail(Request $request)
    {
        $request->validate([
            'holiday_name' => 'required|string|max:255',
            'voucher_code' => 'required|string|max:50',
        ], [
            'holiday_name.required' => 'Vui lòng nhập tên dịp lễ (Ví dụ: 20/10, Valentine, 8/3...)',
            'voucher_code.required' => 'Vui lòng nhập mã giảm giá ưu đãi',
        ]);

        $holidayName = $request->input('holiday_name');
        $voucherCode = strtoupper(trim($request->input('voucher_code')));

        // Lấy danh sách tài khoản có email trong hệ thống
        $users = User::whereNotNull('email')->where('email', '!=', '')->get();

        if ($users->isEmpty()) {
            return back()->with('error', 'Chưa có người dùng nào có email để gửi.');
        }

        $successCount = 0;
        foreach ($users as $user) {
            try {
                // Tái sử dụng chính class HolidayReminderMail đã cấu hình
                Mail::to($user->email)->send(new HolidayReminderMail($user, $holidayName, $voucherCode));
                $successCount++;
            } catch (\Throwable $e) {
                \Log::error("Lỗi gửi mail marketing tới {$user->email}: " . $e->getMessage());
            }
        }

        return back()->with('success', "🎉 Đã gửi thành công chiến dịch '{$holidayName}' tới {$successCount} khách hàng!");
    }
}