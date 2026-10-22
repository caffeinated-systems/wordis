<?php

declare(strict_types=1);

use App\Models\Answer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\travel;
use function Pest\Laravel\travelTo;

beforeEach(function () {
    Answer::factory()->today()->create(['word' => 'tiger']);
});

test('a guess is scored letter by letter against today\'s answer', function (string $guess, array $statuses) {
    postJson('/guesses', ['guess' => $guess, 'row' => 1])
        ->assertOk()
        ->assertJsonPath('statuses', $statuses);
})->with([
    'a wrong guess' => ['tribe', ['correct', 'present', 'present', 'absent', 'present']],
    'a letter already correct elsewhere' => ['tight', ['correct', 'correct', 'correct', 'absent', 'absent']],
    'repeated letters' => ['geese', ['present', 'present', 'absent', 'absent', 'absent']],
    'the answer' => ['tiger', ['correct', 'correct', 'correct', 'correct', 'correct']],
    'an uppercase guess' => ['TIGER', ['correct', 'correct', 'correct', 'correct', 'correct']],
]);

test('a word that is not in the dictionary is rejected and logged', function () {
    Log::spy();

    postJson('/guesses', ['guess' => 'abcde', 'row' => 1])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['guess' => 'Not in word list']);

    Log::shouldHaveReceived('info')->once()->with('Guess not in dictionary', ['guess' => 'abcde']);
});

test('the answer is revealed only when the fifth guess misses', function () {
    foreach (['crane', 'house', 'apple', 'route'] as $index => $guess) {
        postJson('/guesses', ['guess' => $guess, 'row' => $index + 1])->assertJsonMissingPath('answer');
    }

    postJson('/guesses', ['guess' => 'teeth', 'row' => 5])->assertJsonPath('answer', 'tiger');
});

test('a fifth guess that wins does not reveal the answer as a loss', function () {
    foreach (['crane', 'house', 'apple', 'route'] as $index => $guess) {
        postJson('/guesses', ['guess' => $guess, 'row' => $index + 1]);
    }

    postJson('/guesses', ['guess' => 'tiger', 'row' => 5])->assertJsonMissingPath('answer');
});

test('a guess for any row but the next is refused and not recorded', function (int $row) {
    postJson('/guesses', ['guess' => 'crane', 'row' => 1])->assertOk();

    postJson('/guesses', ['guess' => 'house', 'row' => $row])->assertConflict();
    get('/')->assertViewHas('game', fn (array $game): bool => count($game['guesses']) === 1);
})->with([
    'a retry of the recorded row' => 1,
    'skipping ahead' => 3,
]);

test('a finished Game takes no more guesses', function (array $guesses) {
    foreach ($guesses as $index => $guess) {
        postJson('/guesses', ['guess' => $guess, 'row' => $index + 1])->assertOk();
    }

    postJson('/guesses', ['guess' => 'tiger', 'row' => count($guesses) + 1])->assertConflict();
    get('/')->assertViewHas('game', fn (array $game): bool => count($game['guesses']) === count($guesses));
})->with([
    'won' => [['crane', 'tiger']],
    'lost' => [['crane', 'house', 'apple', 'route', 'teeth']],
]);

test('malformed guesses are rejected', function (array $payload, string $field) {
    postJson('/guesses', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor($field);
})->with([
    'too short' => [['guess' => 'tig', 'row' => 1], 'guess'],
    'not letters' => [['guess' => 'tig3r', 'row' => 1], 'guess'],
    'no guess' => [['row' => 1], 'guess'],
    'no row' => [['guess' => 'tiger'], 'row'],
    'a row that is not a number' => [['guess' => 'tiger', 'row' => 'first'], 'row'],
]);

test('loading the page shows the Game so far', function () {
    postJson('/guesses', ['guess' => 'tribe', 'row' => 1]);

    get('/')->assertViewHas('game', [
        'day' => today()->format('j M'),
        'guesses' => [['word' => 'tribe', 'statuses' => ['correct', 'present', 'present', 'absent', 'present']]],
    ]);
});

test('loading the page after a lost Game shows the answer', function () {
    foreach (['crane', 'house', 'apple', 'route', 'teeth'] as $index => $guess) {
        postJson('/guesses', ['guess' => $guess, 'row' => $index + 1]);
    }

    get('/')->assertViewHas('game', fn (array $game): bool => $game['answer'] === 'tiger');
});

test('a finished Game is timed from its first guess to its last', function () {
    postJson('/guesses', ['guess' => 'crane', 'row' => 1])->assertJsonMissingPath('seconds');
    travel(134)->seconds();

    postJson('/guesses', ['guess' => 'tiger', 'row' => 2])->assertJsonPath('seconds', 134);
    get('/')->assertViewHas('game', fn (array $game): bool => $game['seconds'] === 134 && $game['day'] === today()->format('j M'));
});

test('a Game in progress has no time yet', function () {
    postJson('/guesses', ['guess' => 'crane', 'row' => 1]);

    get('/')->assertViewHas('game', fn (array $game): bool => ! array_key_exists('seconds', $game));
});

test('a guess after midnight is scored against the answer the Game started on', function () {
    travelTo(today()->setTime(23, 59));
    Answer::factory()->create(['date' => today()->addDay()->toDateString(), 'word' => 'crane']);
    get('/');

    travelTo(today()->addDay()->setTime(0, 1));

    postJson('/guesses', ['guess' => 'tiger', 'row' => 1])->assertJsonPath('statuses', array_fill(0, 5, 'correct'));
});

test('the first page load after midnight starts the new day\'s Game', function () {
    travelTo(today()->setTime(23, 59));
    Answer::factory()->create(['date' => today()->addDay()->toDateString(), 'word' => 'crane']);
    postJson('/guesses', ['guess' => 'tiger', 'row' => 1]);

    travelTo(today()->addDay()->setTime(0, 1));

    get('/')->assertViewHas('game', fn (array $game): bool => $game['guesses'] === []);
    postJson('/guesses', ['guess' => 'crane', 'row' => 1])->assertJsonPath('statuses', array_fill(0, 5, 'correct'));
});

test('a guess needs the CSRF token from the page', function () {
    // Forgery protection is skipped in the testing environment, so step out of it.
    app()->detectEnvironment(fn (): string => 'local');
    $token = Str::betweenFirst((string) get('/')->getContent(), 'name="csrf-token" content="', '"');

    postJson('/guesses', ['guess' => 'crane', 'row' => 1])->assertStatus(419);
    postJson('/guesses', ['guess' => 'crane', 'row' => 1], ['X-CSRF-TOKEN' => $token])->assertOk();
});

test('guessing is limited to 60 a minute', function () {
    foreach (range(1, 60) as $request) {
        expect(postJson('/guesses', ['guess' => 'crane', 'row' => 1])->status())->not->toBe(429);
    }

    postJson('/guesses', ['guess' => 'crane', 'row' => 1])->assertTooManyRequests();
});

test('the rate limit counts each player behind Cloudflare separately', function () {
    foreach (range(1, 60) as $request) {
        expect(postJson('/guesses', ['guess' => 'crane', 'row' => 1], ['CF-Connecting-IP' => '203.0.113.1'])->status())->not->toBe(429);
    }

    postJson('/guesses', ['guess' => 'crane', 'row' => 1], ['CF-Connecting-IP' => '203.0.113.1'])->assertTooManyRequests();
    expect(postJson('/guesses', ['guess' => 'crane', 'row' => 1], ['CF-Connecting-IP' => '203.0.113.2'])->status())->not->toBe(429);
});
