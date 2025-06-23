<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrackingNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build()
    {
        return $this->subject('Tracking Information Available - Order #' . $this->order->order_number)
                    ->view('emails.tracking-notification')
                    ->with([
                        'order' => $this->order,
                    ]);
    }
}