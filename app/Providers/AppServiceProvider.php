<?php

namespace App\Providers;

use App\Models\ScanFile;
use Illuminate\Support\Facades\Route;
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
        // ScanFile uses a non-standard primary key (Scan_Id) — tell Laravel how to resolve it
        Route::bind('scan', fn ($value) => ScanFile::where('Scan_Id', $value)->firstOrFail());
    }
}
