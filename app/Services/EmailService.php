<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderStatusUpdateMail;
use App\Mail\TrackingUpdateMail;
use App\Mail\PasswordResetMail;
use App\Mail\WelcomeMail;

class EmailService
{
    public function sendOrderConfirmation(Order $order)
    {
        try {
            $order->load(['user', 'items.product']);
            Mail::to($order->user->email)->send(new OrderConfirmationMail($order));
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send order confirmation email: ' . $e->getMessage());
            return false;
        }
    }

    public function sendOrderStatusUpdate(Order $order)
    {
        try {
            // Load relationships if not already loaded
            if (!$order->relationLoaded('items')) {
                $order->load(['items.product']);
            }
            
            // For guest orders, we don't have a user relationship
            if ($order->user_id && !$order->relationLoaded('user')) {
                $order->load('user');
            }
            
            Mail::to($order->email)->send(new OrderStatusUpdateMail($order));
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send order status update email: ' . $e->getMessage());
            return false;
        }
    }

    public function sendTrackingUpdate(Order $order)
    {
        try {
            // Load relationships if not already loaded
            if (!$order->relationLoaded('items')) {
                $order->load(['items.product']);
            }
            
            // For guest orders, we don't have a user relationship
            if ($order->user_id && !$order->relationLoaded('user')) {
                $order->load('user');
            }
            
            Mail::to($order->email)->send(new TrackingUpdateMail($order));
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send tracking update email: ' . $e->getMessage());
            return false;
        }
    }

    public function sendPasswordReset(User $user, $resetUrl)
    {
        try {
            Mail::to($user->email)->send(new PasswordResetMail($user, $resetUrl));
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send password reset email: ' . $e->getMessage());
            return false;
        }
    }

    public function sendWelcomeEmail(User $user)
    {
        try {
            Mail::to($user->email)->send(new \App\Mail\WelcomeMail($user));
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send welcome email: ' . $e->getMessage());
            return false;
        }
    }
}
