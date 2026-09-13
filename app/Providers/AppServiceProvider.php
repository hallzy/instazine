<?php

namespace App\Providers;

use App\Enums\UserLevel;
use App\Models\Health;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewInstance;

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
        View::composer('layouts.app', function (ViewInstance $view): void {
            $view->with('latestHealth', null);
            $view->with('dailyDeviceMessageCount', 0);

            $user = request()->user();

            if (! request()->routeIs('admin', 'admin.*') || $user?->level !== UserLevel::Honcho) {
                return;
            }

            $view->with('latestHealth', Health::query()
                ->orderByDesc('Date')
                ->orderByDesc('H_id')
                ->first(['Date', 'Message']));

            $requestedTimezone = request()->cookie('instazine_timezone');
            $timezone = is_string($requestedTimezone)
                && in_array($requestedTimezone, \DateTimeZone::listIdentifiers(), true)
                    ? $requestedTimezone
                    : config('app.timezone');
            $startOfDay = now($timezone)->startOfDay()->utc();
            $endOfDay = now($timezone)->endOfDay()->utc();

            $view->with('dailyDeviceMessageCount', Health::query()
                ->where('Message', 'like', '0x18%')
                ->whereBetween('Date', [$startOfDay, $endOfDay])
                ->count());
        });
    }
}
