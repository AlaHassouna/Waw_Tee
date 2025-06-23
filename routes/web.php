<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return ['message' => 'E-commerce API is running!', 'version' => '1.0.0'];
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now(),
        'service' => 'E-commerce API'
    ]);
});
Route::get('/test-email', function () {
    try {
        // Test simple
        Mail::raw('Test email depuis Laravel avec GoDaddy SMTP', function ($message) {
            $message->to('business2060vr@gmail.com')
                    ->subject('Test Email GoDaddy')
                    ->from(config('mail.from.address'), config('mail.from.name'));
        });
        
        return response()->json([
            'status' => 'success',
            'message' => 'Email envoyé avec succès!',
            'config' => [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'username' => config('mail.mailers.smtp.username'),
                'from' => config('mail.from')
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'config' => [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'username' => config('mail.mailers.smtp.username'),
                'from' => config('mail.from')
            ]
        ]);
    }
});