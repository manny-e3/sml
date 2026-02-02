<?php

namespace App\Mail;

use App\Models\PendingSecurity;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SecurityRequestApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $pending;

    /**
     * Create a new message instance.
     */
    public function __construct(PendingSecurity $pending)
    {
        $this->pending = $pending;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Security Request Approved')
                    ->view('emails.securities.approved');
    }
}
