<?php

declare(strict_types=1);

use App\Http\Controllers\GameController;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;
use Laravel\WorkOS\Http\Requests\AuthKitAuthenticationRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLoginRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLogoutRequest;
use Laravel\WorkOS\WorkOS;
use Symfony\Component\HttpFoundation\Response;
use WorkOS\Exception\NotFoundException;
use WorkOS\Exception\WorkOSException;
use WorkOS\UserManagement;

Route::get('/', [GameController::class, 'show'])->block();
Route::get('/history', [GameController::class, 'history'])->middleware('auth')->name('history');
Route::view('/privacy', 'privacy')->name('privacy')->block();

// Blocking queues a session's guesses and the pages a player opens mid-guess, so none writes back a stale Game over another's.
Route::post('/guesses', [GameController::class, 'guess'])
    ->middleware('throttle:guesses')
    ->block();

Route::get('/login', fn (AuthKitLoginRequest $request): Response => $request->redirect($request->boolean('signup') ? ['screenHint' => 'sign-up'] : []))
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
        $user = $request->authenticate();
        assert($user instanceof User);
        GameController::carryOver($request, $user);
    } catch (WorkOSException $exception) {
        report($exception);
    }

    return redirect('/');
})->middleware('guest')->block();

// Refreshing the WorkOS tokens first lets logout end the WorkOS session too, not just ours.
Route::post('/logout', fn (AuthKitLogoutRequest $request): Response => $request->logout())
    ->middleware(ValidateSessionWithWorkOS::class)
    ->name('logout');

// WorkOS goes first, so if it fails the account stays whole and the player can try again. Their Games cascade with the User.
Route::delete('/account', function (Request $request, #[CurrentUser] User $user): RedirectResponse {
    if ($user->workos_id !== null) {
        WorkOS::configure();

        try {
            (new UserManagement)->deleteUser($user->workos_id);
        } catch (NotFoundException) {
            // The WorkOS login is already gone.
        } catch (WorkOSException $exception) {
            report($exception);

            return redirect()->route('history')->with('error', "Your account couldn't be deleted. Please try again.");
        }
    }

    // Logging out saves a fresh remember token, which would re-insert a User deleted first.
    Auth::guard('web')->logout();
    $user->delete();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/')->with('status', 'Your account was deleted.');
})->middleware('auth')->name('account.destroy')->block();
