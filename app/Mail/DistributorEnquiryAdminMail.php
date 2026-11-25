<?php

namespace App\Mail;

use App\Models\DistributorEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DistributorEnquiryAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DistributorEnquiry $enquiry)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New "Become a Distributor" Enquiry — ' . $this->enquiry->company_name,
            replyTo: [
                new Address($this->enquiry->email, $this->enquiry->contact_person),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.distributor_enquiry_admin',
        );
    }
}
