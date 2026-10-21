<?php

declare(strict_types=1);

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;
use Laravel\WorkOS\Http\Requests\AuthKitAuthenticationRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLoginRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLogoutRequest;
use Symfony\Component\HttpFoundation\Response;
use WorkOS\Exception\WorkOSException;

Route::get('/', [GameController::class, 'show'])->block();
Route::view('/privacy', 'privacy')->name('privacy');

// Blocking queues a session's page loads and guesses, so neither writes back a stale Game over the other's.
Route::post('/guesses', [GameController::class, 'guess'])
    ->middleware('throttle:guesses')
    ->block();

Route::get('/login', fn (AuthKitLoginRequest $request): Response => $request->redirect())
    ->middleware('guest')
    ->name('login');

Route::get('/authenticate', function (AuthKitAuthenticationRequest $request): Response {
    // The package's state check passes when both sides are missing, so a login must be pending.
    abort_unless($request->session()->has('state') && is_string($request->query('state')), 403);

    // A player who cancels on AuthKit comes back with an error and no code.
    if (! is_string($request->query('code'))) {
        return redirect('/');
    }

    try {
        $request->authenticate();
    } catch (WorkOSException $exception) {
        report($exception);
    }

    return redirect('/');
})->middleware('guest');

// Refreshing the WorkOS tokens first lets logout end the WorkOS session too, not just ours.
Route::post('/logout', fn (AuthKitLogoutRequest $request): Response => $request->logout())
    ->middleware(ValidateSessionWithWorkOS::class)
    ->name('logout');
