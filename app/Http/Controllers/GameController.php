<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * @phpstan-type Game array{date: string, guesses: list<string>}
 */
class GameController extends Controller
{
    /**
     * Guesses a player gets before the answer is revealed. The game in resources/js/game.ts allows the same number.
     */
    public const int GUESSES_ALLOWED = 5;

    /**
     * Show the player's Game, starting today's when they have none or theirs belongs to an earlier answer.
     */
    public function show(Request $request): View
    {
        $game = self::game($request);

        if ($game === null || $game['date'] !== today()->toDateString()) {
            $game = self::start($request);
        }

        $answer = Answer::for($game['date']);

        return view('welcome', ['game' => [
            'guesses' => array_map(fn (string $guess): array => ['word' => $guess, 'statuses' => self::score($guess, $answer)], $game['guesses']),
            ...(self::isLost($game['guesses'], $answer) ? ['answer' => $answer] : []),
        ]]);
    }

    /**
     * Score a guess against the answer the Game started on, revealing the answer when the last guess misses.
     */
    public function guess(Request $request): JsonResponse
    {
        $request->validate([
            'guess' => ['bail', 'required', 'string', 'regex:/^[a-zA-Z]{5}\z/', function (string $attribute, string $guess, Closure $fail): void {
                if (! in_array(Str::upper($guess), self::dictionary(), true)) {
                    Log::info('Guess not in dictionary', ['guess' => Str::lower($guess)]);
                    $fail('Not in word list');
                }
            }],
            'row' => ['required', 'integer'],
        ]);

        // The transaction locks the account's Game, so another device's guess can't write over this one.
        // SQLite ignores the lock but fails the later of two writes instead, and its retry gets a 409.
        return DB::transaction(function () use ($request): JsonResponse {
            $game = self::game($request) ?? ['date' => today()->toDateString(), 'guesses' => []];
            $answer = Answer::for($game['date']);

            // The server counts the guesses; the row only stops a stale tab or a retried request from recording a guess the player didn't mean for this one.
            abort_if(
                self::isFinished($game['guesses'], $answer) || $request->integer('row') !== count($game['guesses']) + 1,
                409,
                'This game moved on',
            );

            $guess = $request->string('guess')->lower()->value();
            $game['guesses'][] = $guess;
            self::save($request, $game);

            return response()->json([
                'statuses' => self::score($guess, $answer),
                ...(self::isLost($game['guesses'], $answer) ? ['answer' => $answer] : []),
            ]);
        });
    }

    /**
     * Move a guest's Game onto the account they just signed in to, unless the account's Game for that answer should win:
     * an account Game with guesses is kept, unless the guest finished theirs and the account hasn't.
     */
    public static function carryOver(Request $request, User $user): void
    {
        /** @var Game|null $guestGame */
        $guestGame = $request->session()->pull('game');

        if ($guestGame === null || $guestGame['guesses'] === []) {
            return;
        }

        $answer = Answer::for($guestGame['date']);

        // Locked like a guess, so a guess another device makes meanwhile isn't written over. Deadlocks and busy errors are retried.
        DB::transaction(function () use ($user, $guestGame, $answer): void {
            $accountGame = $user->games()->where('date', $guestGame['date'])->lockForUpdate()->first();

            if ($accountGame === null) {
                // A row another device created meanwhile is read again, under the lock.
                $user->games()->createOrFirst(['date' => $guestGame['date']], ['guesses' => $guestGame['guesses']]);
                $accountGame = $user->games()->where('date', $guestGame['date'])->lockForUpdate()->sole();
            }

            if ($accountGame->guesses === [] || (self::isFinished($guestGame['guesses'], $answer) && ! self::isFinished($accountGame->guesses, $answer))) {
                $accountGame->update(['guesses' => $guestGame['guesses']]);
            }
        }, 3);
    }

    /**
     * The player's latest Game: the account's for a signed-in player, the session's for a guest.
     *
     * @return Game|null
     */
    private static function game(Request $request): ?array
    {
        if ($request->user() === null) {
            /** @var Game|null */
            return $request->session()->get('game');
        }

        $game = $request->user()->games()->latest('date')->lockForUpdate()->first();

        return $game?->only(['date', 'guesses']);
    }

    /**
     * Start today's Game, or pick up the one another device already started.
     *
     * @return Game
     */
    private static function start(Request $request): array
    {
        if ($request->user() === null) {
            $request->session()->put('game', $game = ['date' => today()->toDateString(), 'guesses' => []]);

            return $game;
        }

        return $request->user()->games()->firstOrCreate(['date' => today()->toDateString()], ['guesses' => []])->only(['date', 'guesses']);
    }

    /**
     * @param  Game  $game
     */
    private static function save(Request $request, array $game): void
    {
        if ($request->user() === null) {
            $request->session()->put('game', $game);

            return;
        }

        $request->user()->games()->updateOrCreate(['date' => $game['date']], ['guesses' => $game['guesses']]);
    }

    /**
     * @param  list<string>  $guesses
     */
    private static function isFinished(array $guesses, string $answer): bool
    {
        return in_array($answer, $guesses, true) || count($guesses) >= self::GUESSES_ALLOWED;
    }

    /**
     * @param  list<string>  $guesses
     */
    private static function isLost(array $guesses, string $answer): bool
    {
        return count($guesses) === self::GUESSES_ALLOWED && ! in_array($answer, $guesses, true);
    }

    /**
     * Mark each letter correct, present or absent. A repeated letter is only
     * marked present as many times as it appears in the answer but not in place.
     *
     * @return list<'correct'|'present'|'absent'>
     */
    private static function score(string $guess, string $answer): array
    {
        $statuses = array_fill(0, strlen($guess), 'absent');
        $unmatched = str_split($answer);

        foreach (str_split($guess) as $position => $letter) {
            if ($letter === $answer[$position]) {
                $statuses[$position] = 'correct';
                unset($unmatched[$position]);
            }
        }

        foreach (str_split($guess) as $position => $letter) {
            $match = array_search($letter, $unmatched, true);

            if ($statuses[$position] !== 'correct' && $match !== false) {
                $statuses[$position] = 'present';
                unset($unmatched[$match]);
            }
        }

        return array_values($statuses);
    }

    /**
     * @return list<string>
     */
    private static function dictionary(): array
    {
        return array_values(array_filter(File::json(resource_path('words/dictionary.json'), JSON_THROW_ON_ERROR), is_string(...)));
    }
}
