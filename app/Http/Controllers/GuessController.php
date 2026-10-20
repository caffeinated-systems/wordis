<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GuessController extends Controller
{
    /**
     * Guesses a player gets before the answer is revealed. The game in resources/js/game.ts allows the same number.
     */
    public const GUESSES_ALLOWED = 5;

    /**
     * Score a guess against today's answer, revealing the answer when the last guess misses.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'guess' => ['bail', 'required', 'string', 'regex:/^[a-zA-Z]{5}$/', function (string $attribute, string $guess, Closure $fail): void {
                if (! in_array(Str::upper($guess), self::dictionary(), true)) {
                    Log::info('Guess not in dictionary', ['guess' => Str::lower($guess)]);
                    $fail('Not in word list');
                }
            }],
            'attempt' => ['required', 'integer', 'between:1,'.self::GUESSES_ALLOWED],
        ]);

        $guess = Str::lower($validated['guess']);
        $answer = Answer::today();
        $lost = $guess !== $answer && (int) $validated['attempt'] === self::GUESSES_ALLOWED;

        return response()->json([
            'statuses' => self::score($guess, $answer),
            ...($lost ? ['answer' => $answer] : []),
        ]);
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

        return $statuses;
    }

    /**
     * @return list<string>
     */
    private static function dictionary(): array
    {
        return json_decode(file_get_contents(resource_path('words/dictionary.json')), true, flags: JSON_THROW_ON_ERROR);
    }
}
