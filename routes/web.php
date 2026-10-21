<?php

declare(strict_types=1);

use App\Http\Controllers\GuessController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;
use Laravel\WorkOS\Http\Requests\AuthKitAuthenticationRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLoginRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLogoutRequest;
use Laravel\WorkOS\User as WorkOSUser;
use Symfony\Component\HttpFoundation\Response;
use WorkOS\Exception\WorkOSException;

Route::view('/', 'welcome');

// Scoring a guess changes nothing, so it skips sessions and CSRF and doesn't wake the database for a session.
Route::post('/guesses', GuessController::class)
    ->withoutMiddleware('web')
    ->middleware('throttle:guesses');

Route::get('/login', fn (AuthKitLoginRequest $request): Response => $request->redirect())
    ->middleware('guest')
    ->name('login');

Route::get('/authenticate', function (AuthKitAuthenticationRequest $request): Response {
    try {
        // A player recreated in WorkOS gets a new id but keeps their verified email, so the email finds them too.
        $request->authenticate(findUsing: function (WorkOSUser $workOsUser): ?User {
            $user = User::firstWhere('workos_id', $workOsUser->id) ?? User::firstWhere('email', $workOsUser->email);
            $user?->update(['workos_id' => $workOsUser->id]);

            return $user;
        });
    } catch (WorkOSException $exception) {
        report($exception);
    }

    return redirect('/');
})->middleware('guest');

// Refreshing the WorkOS tokens first lets logout end the WorkOS session too, not just ours.
Route::post('/logout', fn (AuthKitLogoutRequest $request): Response => $request->logout())
    ->middleware(ValidateSessionWithWorkOS::class)
    ->name('logout');
