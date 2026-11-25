<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserRegistrationAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New User Registration — ' . $this->user->name,
            replyTo: [
                new Address($this->user->email, $this->user->name),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user_registration_admin',
        );
    }
}
