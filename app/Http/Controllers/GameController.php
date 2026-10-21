<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Answer;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * Show the player's Game, starting today's when the session's Game belongs to an earlier answer.
     */
    public function show(Request $request): View
    {
        $game = self::game($request);

        if ($game['date'] !== today()->toDateString()) {
            $game = ['date' => today()->toDateString(), 'guesses' => []];
        }

        $request->session()->put('game', $game);

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
        ]);

        $game = self::game($request);
        $answer = Answer::for($game['date']);

        abort_if(in_array($answer, $game['guesses'], true) || count($game['guesses']) >= self::GUESSES_ALLOWED, 409, 'This game is over');

        $guess = $request->string('guess')->lower()->value();
        $game['guesses'][] = $guess;
        $request->session()->put('game', $game);

        return response()->json([
            'guess' => count($game['guesses']),
            'statuses' => self::score($guess, $answer),
            ...(self::isLost($game['guesses'], $answer) ? ['answer' => $answer] : []),
        ]);
    }

    /**
     * The session's Game, or a new one for today when the session has none.
     *
     * @return Game
     */
    private static function game(Request $request): array
    {
        /** @var Game */
        return $request->session()->get('game', ['date' => today()->toDateString(), 'guesses' => []]);
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
