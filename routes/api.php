<?php
// dd('api.php DID execute');  
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TodoController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\LocationController;

/*
|--------------------------------------------------------------------------
| API Routes
|-------------------------------------------------------------------------- 
| All routes below are automatically prefixed with /api        (handled by
| RouteServiceProvider) and then with /v1 by the prefix() call.
| Example final URL for register:  http://127.0.0.1:8000/api/v1/register
|--------------------------------------------------------------------------
*/

Route::prefix('v1')
    ->name('api.v1.')                   // route names like api.v1.register
    ->group(function () {

        /* ──────── Public auth ──────── */
        Route::post('register', [AuthController::class, 'register'])
            ->name('register');

        Route::post('login',    [AuthController::class, 'login'])
            ->name('login');

        /* ─────── Routes that need a valid Sanctum token ─────── */
        Route::middleware('auth:sanctum')->group(function () {

            /* Auth */
            Route::get ('me',     [AuthController::class, 'me'])
                ->name('me');

            Route::post('logout', [AuthController::class, 'logout'])
                ->name('logout');

            Route::get ('load-data', [AuthController::class, 'loadData'])
                ->name('load-data');

            /* Resources */
            Route::apiResource('todos',     TodoController::class);
            Route::apiResource('expenses',  ExpenseController::class);
            Route::apiResource('locations', LocationController::class)
                 ->only(['index', 'store', 'show', 'destroy']);
        });

        /* ─────── Admin-only examples ─────── */
        Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
            Route::get('admin/ping', fn () => response()->json(['success' => true]))
                 ->name('admin.ping');
        });
    });
