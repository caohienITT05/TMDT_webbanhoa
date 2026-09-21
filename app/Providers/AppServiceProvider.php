<?php

namespace App\Providers;

use App\Models\CustomOrderRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        View::composer('layouts.admin', function ($view): void {
            if (!Schema::hasTable('custom_order_requests')) {
                $view->with([
                    'customOrderUnreadCount' => 0,
                    'customOrderNotifications' => collect(),
                ]);

                return;
            }

            $view->with([
                'customOrderUnreadCount' => CustomOrderRequest::query()->whereNull('admin_viewed_at')->count(),
                'customOrderNotifications' => CustomOrderRequest::query()
                    ->whereNull('admin_viewed_at')
                    ->latest()
                    ->take(5)
                    ->get(),
            ]);
        });
    }
}
