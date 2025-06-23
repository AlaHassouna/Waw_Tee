<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrackingUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build()
    {
        // Create a user object for the template if order has user_id
        $user = $this->order->user;
        if (!$user && $this->order->user_id) {
            // Try to load user if not already loaded
            $user = \App\Models\User::find($this->order->user_id);
        }
        
        // If still no user (guest order), create a fake user object with order data
        if (!$user) {
            $user = (object) [
                'name' => $this->order->first_name . ' ' . $this->order->last_name,
                'email' => $this->order->email
            ];
        }

        return $this->subject('Numéro de suivi disponible - Commande ' . $this->order->order_number)
                    ->view('emails.tracking-update')
                    ->with([
                        'order' => $this->order,
                        'user' => $user,
                    ]);
    }
}
