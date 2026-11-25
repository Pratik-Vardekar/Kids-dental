<?php

namespace App\Mail;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $orderSummary;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing([
            'user',
            'items.product.gstSetting',
            'items.variant',
            'shippingAddress',
            'billingAddress',
            'addresses',
            'orderPayments',
            'refunds',
        ]);

        $this->orderSummary = $this->order->getOrderSummary();
    }

    public function envelope(): Envelope
    {
        $label = $this->order->display_invoice_no;

        return new Envelope(
            subject: 'Invoice & Order Confirmation — ' . $label,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order_success',
        );
    }

    public function attachments(): array
    {
        try {
            $pdf = Pdf::loadView('admin.order.invoice', ['order' => $this->order])
                ->setPaper('a4');

            $filename = $this->order->invoicePdfFilename();

            return [
                Attachment::fromData(fn () => $pdf->output(), $filename)
                    ->withMime('application/pdf'),
            ];
        } catch (\Throwable $e) {
            Log::error('Order invoice PDF attach failed: ' . $e->getMessage(), [
                'order_id' => $this->order->id,
            ]);

            return [];
        }
    }
}
