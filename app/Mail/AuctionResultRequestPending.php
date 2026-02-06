<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\PendingAuctionResult;

class AuctionResultRequestPending extends Mailable
{
    use Queueable, SerializesModels;

    public $pending;
    public $dashboardUrl;
    public $requester;

    public function __construct(PendingAuctionResult $pending, $requester = null)
    {
        $this->pending = $pending;
        $this->requester = $requester;
        $this->dashboardUrl = config('app.frontend_url', config('app.url')) . '/admin/auction-results/pending'; 
    }

    public function envelope(): Envelope
    {
        $type = ucfirst($this->pending->request_type);
        return new Envelope(
            subject: "New Auction Result Request: {$type} - " . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auction-result-request-pending',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
