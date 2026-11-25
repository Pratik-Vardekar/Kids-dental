<?php

namespace App\Mail;

use App\Models\DistributorEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DistributorEnquiryUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DistributorEnquiry $enquiry)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thank you for your interest in becoming a Kids-e-Dental Distributor',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.distributor_enquiry_user',
        );
    }
}
