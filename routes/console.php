<?php

use App\Mail\HolidayReminderMail;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reminder:holiday {--holiday=20/10 - Phụ Nữ Việt Nam} {--code=BLOOM01}', function () {
    $holiday = $this->option('holiday');
    $code = $this->option('code');

    $this->info("Đang bắt đầu chiến dịch gửi thư nhắc lễ: {$holiday}...");

    // Lấy tối đa 5 người dùng có email trong database để gửi test
    $users = User::whereNotNull('email')->take(5)->get();

    if ($users->isEmpty()) {
        $this->warn("Chưa có tài khoản người dùng nào có email trong database!");
        return;
    }

    foreach ($users as $user) {
        try {
            Mail::to($user->email)->send(new HolidayReminderMail($user, $holiday, $code));
            $this->line(" -> Đã gửi thành công tới: {$user->email}");
        } catch (\Throwable $e) {
            $this->error(" -> Lỗi gửi tới {$user->email}: " . $e->getMessage());
        }
    }

    $this->info("✅ Đã hoàn thành gửi thư nhắc lễ {$holiday} thành công!");
})->purpose('Gửi email nhắc nhở dịp lễ và tặng voucher cho khách hàng');