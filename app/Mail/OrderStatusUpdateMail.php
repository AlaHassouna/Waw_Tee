<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $customerName;

    public function __construct(Order $order)
    {
        $this->order = $order;
        
        // Determine customer name - prioritize user name, then order first/last name
        if ($order->user && $order->user->name) {
            $this->customerName = $order->user->name;
        } elseif ($order->first_name && $order->last_name) {
            $this->customerName = $order->first_name . ' ' . $order->last_name;
        } elseif ($order->first_name) {
            $this->customerName = $order->first_name;
        } else {
            $this->customerName = 'Customer';
        }
    }

    public function build()
    {
        return $this->subject('Order Status Update - ' . $this->order->order_number)
                    ->view('emails.order-status-update')
                    ->with([
                        'order' => $this->order,
                        'customerName' => $this->customerName
                    ]);
    }
}
