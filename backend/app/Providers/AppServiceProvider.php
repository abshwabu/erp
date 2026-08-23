<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Modules\Inventory\Models\StockMovement::observe(\App\Modules\Inventory\Observers\StockLevelObserver::class);

        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Inventory\Events\LowStockDetected::class,
            \App\Modules\Inventory\Listeners\LowStockListener::class
        );

        \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            return rtrim($frontendUrl, '/') . '/reset-password?token=' . urlencode($token) . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
