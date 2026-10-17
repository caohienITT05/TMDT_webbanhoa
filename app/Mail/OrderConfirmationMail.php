<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public ?string $guestOrderUrl = null)
    {
        $this->order->loadMissing(['items', 'deliverySlot']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'BloomGift đã nhận đơn hàng #' . ($this->order->order_code ?? $this->order->id));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order_confirmation');
    }

    public function attachments(): array
    {
        return [];
    }
}
