<?php

use App\Mail\HolidayReminderMail;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reminder:holiday {--holiday= : Tên dịp lễ} {--code= : Mã voucher khuyến mại}', function () {
    // Gán giá trị mặc định an toàn bằng PHP, không bị lỗi khoảng trắng command line
    $holiday = $this->option('holiday') ?: '20/10 - Phụ Nữ Việt Nam';
    $code = $this->option('code') ?: 'BLOOM01';

    $this->info("==================================================");
    $this->info("🌸 BẮT ĐẦU CHIẾN DỊCH EMAIL MARKETING DỊP LỄ");
    $this->info("   Dịp lễ: {$holiday}");
    $this->info("   Voucher: {$code}");
    $this->info("==================================================");

    // Lấy tối đa 5 người dùng có email hợp lệ để test
    $users = User::whereNotNull('email')
        ->where('email', '!=', '')
        ->take(5)
        ->get();

    if ($users->isEmpty()) {
        $this->warn("⚠️ Chưa có tài khoản người dùng nào có email trong cơ sở dữ liệu!");
        return;
    }

    $successCount = 0;
    $failCount = 0;

    foreach ($users as $user) {
        try {
            Mail::to($user->email)->send(new HolidayReminderMail($user, $holiday, $code));
            $this->line(" -> [Thành công] Đã gửi thư tới: {$user->email}");
            $successCount++;
        } catch (\Throwable $e) {
            $this->error(" -> [Thất bại] Lỗi gửi tới {$user->email}: " . $e->getMessage());
            $failCount++;
        }
    }

    $this->info("--------------------------------------------------");
    $this->info("✅ Hoàn tất chiến dịch! Đã gửi: {$successCount} thành công, {$failCount} thất bại.");
})->purpose('Gửi email nhắc nhở dịp lễ và tặng voucher cho khách hàng');