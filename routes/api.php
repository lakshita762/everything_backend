<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\LocationLiveController;
use App\Http\Controllers\Api\V1\LocationShareController;
use App\Http\Controllers\Api\V1\LocationShareInviteController;
use App\Http\Controllers\Api\V1\LocationShareParticipantController;
use App\Http\Controllers\Api\V1\TodoListController;
use App\Http\Controllers\Api\V1\TodoListInviteController;
use App\Http\Controllers\Api\V1\TodoListShareController;
use App\Http\Controllers\Api\V1\TodoTaskController;

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function () {
        Route::prefix('auth')->middleware('throttle:60,1')->group(function () {
            Route::post('login', [AuthController::class, 'login'])->name('login');
            Route::get('redirect', [AuthController::class, 'getRedirectUrl'])->name('redirect');
            Route::get('callback', [AuthController::class, 'handleCallback'])->name('callback');
        });
        Route::middleware('auth:sanctum')->group(function () {
            Route::prefix('auth')->group(function () {
                Route::get('me', [AuthController::class, 'me'])->name('me');
                Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            });
            Route::get('todo-lists', [TodoListController::class, 'index']);
            Route::post('todo-lists', [TodoListController::class, 'store']);
            Route::patch('todo-lists/{todoList}', [TodoListController::class, 'update']);
            Route::delete('todo-lists/{todoList}', [TodoListController::class, 'destroy']);

            Route::post('todo-lists/{todoList}/tasks', [TodoTaskController::class, 'store']);
            Route::patch('todo-lists/{todoList}/tasks/{task}', [TodoTaskController::class, 'update']);
            Route::delete('todo-lists/{todoList}/tasks/{task}', [TodoTaskController::class, 'destroy']);

            Route::post('todo-lists/{todoList}/share', [TodoListShareController::class, 'store'])->middleware('throttle:30,1');
            Route::patch('todo-lists/{todoList}/share/{user}', [TodoListShareController::class, 'update']);
            Route::delete('todo-lists/{todoList}/share/{user}', [TodoListShareController::class, 'destroy']);

            Route::get('todo-lists/invites', [TodoListInviteController::class, 'index']);
            Route::post('todo-lists/invites/{invite}/accept', [TodoListInviteController::class, 'accept']);
            Route::post('todo-lists/invites/{invite}/decline', [TodoListInviteController::class, 'decline']);

            Route::get('location-shares', [LocationShareController::class, 'index']);
            Route::post('location-shares', [LocationShareController::class, 'store']);
            Route::post('location-shares/{share}/participants', [LocationShareParticipantController::class, 'store'])->middleware('throttle:30,1');
            Route::patch('location-shares/{share}/participants/{participant}', [LocationShareParticipantController::class, 'update']);
            Route::delete('location-shares/{share}/participants/{participant}', [LocationShareParticipantController::class, 'destroy']);
            Route::post('location-shares/{share}/stop', [LocationShareController::class, 'stop']);

            Route::post('location-shares/invites/{participant}/accept', [LocationShareInviteController::class, 'accept']);
            Route::post('location-shares/invites/{participant}/decline', [LocationShareInviteController::class, 'decline']);

            Route::post('locations/live', [LocationLiveController::class, 'store'])->middleware('throttle:120,1');
            Route::get('locations/live/{session_token}', [LocationLiveController::class, 'stream']);

            Route::apiResource('expenses', ExpenseController::class);
            Route::apiResource('locations', LocationController::class)->only(['index', 'store', 'show', 'destroy']);
        });

        Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
            Route::get('admin/ping', fn () => response()->json(['success' => true]))->name('admin.ping');
        });
    });


