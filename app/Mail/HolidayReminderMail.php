<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HolidayReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public string $holidayName;
    public string $voucherCode;

    public function __construct($user, string $holidayName = 'Ngày Phụ Nữ Việt Nam 20/10', string $voucherCode = 'BLOOM01')
    {
        $this->user = $user;
        $this->holidayName = $holidayName;
        $this->voucherCode = $voucherCode;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🌸 BloomGift: Sắp đến ' . $this->holidayName . ' - Nhận ngay ưu đãi tặng hoa!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.holiday_reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}