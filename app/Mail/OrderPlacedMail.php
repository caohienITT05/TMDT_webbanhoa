<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlacedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🌸 [BloomGift] Xác nhận đặt hàng thành công #' . ($this->order->order_code ?? $this->order->id),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order_placed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}