<?php


namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /** The path to the “home” route for your application. */
    public const HOME = '/';

    public function boot(): void
    {
        // This closure registers all route files
        $this->routes(function () {
            /* API routes → /api/... */
            Route::middleware('api')
                 ->prefix('api')
                 ->group(base_path('routes/api.php'));

            /* Web routes → / */
            Route::middleware('web')
                 ->group(base_path('routes/web.php'));
        });
    }
}
