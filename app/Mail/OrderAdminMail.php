<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $orderSummary;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing([
            'user',
            'items.product.gstSetting',
            'shippingAddress',
            'billingAddress',
        ]);

        $this->orderSummary = $this->order->getOrderSummary();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Order — ' . $this->order->display_order_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order_admin',
        );
    }
}
