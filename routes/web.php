<?php

declare(strict_types=1);

use App\Http\Controllers\GuessController;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Requests\AuthKitAuthenticationRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLoginRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLogoutRequest;
use Symfony\Component\HttpFoundation\Response;

Route::view('/', 'welcome');

// Scoring a guess changes nothing, so it skips sessions and CSRF and doesn't wake the database for a session.
Route::post('/guesses', GuessController::class)
    ->withoutMiddleware('web')
    ->middleware('throttle:guesses');

Route::get('/login', fn (AuthKitLoginRequest $request): Response => $request->redirect())
    ->middleware('guest')
    ->name('login');

Route::get('/authenticate', function (AuthKitAuthenticationRequest $request): Response {
    $request->authenticate();

    return redirect('/');
})->middleware('guest');

Route::post('/logout', fn (AuthKitLogoutRequest $request): Response => $request->logout())
    ->middleware('auth')
    ->name('logout');
