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
        Route::prefix('auth')->middleware('throttle:60,1')->group(function () {
            Route::post('register', [AuthController::class, 'register'])
            ->name('register');
            Route::post('login',    [AuthController::class, 'login'])
                ->name('login');
            Route::post('google',   [AuthController::class, 'google'])
                ->name('google');
        });

        /* ─────── Routes that need a valid Sanctum token ─────── */
        Route::middleware('auth:sanctum')->group(function () {

            /* Auth */
            Route::prefix('auth')->group(function () {
                Route::get ('me',     [AuthController::class, 'me'])
                    ->name('me');
                Route::post('logout', [AuthController::class, 'logout'])
                    ->name('logout');
            });

            Route::get ('load-data', [AuthController::class, 'loadData'])
                ->name('load-data');

            /* Resources */
            Route::apiResource('todos',     TodoController::class);
            Route::apiResource('expenses',  ExpenseController::class);
            Route::apiResource('locations', LocationController::class)
                 ->only(['index', 'store', 'show', 'destroy']);

            // Live location sessions
            Route::post('live-sessions', [\App\Http\Controllers\Api\V1\LiveSessionController::class, 'store']);
            Route::post('live-sessions/{session_id}/update', [\App\Http\Controllers\Api\V1\LiveSessionController::class, 'update'])->middleware(\App\Http\Middleware\ThrottlePerSession::class);
            Route::get('live-sessions/{session_id}', [\App\Http\Controllers\Api\V1\LiveSessionController::class, 'show']);
            Route::post('live-sessions/{session_id}/end', [\App\Http\Controllers\Api\V1\LiveSessionController::class, 'end']);
            Route::get('live-sessions', [\App\Http\Controllers\Api\V1\LiveSessionController::class, 'listByOwner']);
            Route::get('live-sessions/{session_id}/events', [\App\Http\Controllers\Api\V1\LiveSessionController::class, 'events']);
        });

        /* ─────── Admin-only examples ─────── */
        Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
            Route::get('admin/ping', fn () => response()->json(['success' => true]))
                 ->name('admin.ping');
        });
    });
