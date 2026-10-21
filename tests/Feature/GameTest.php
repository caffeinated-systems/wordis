<?php

declare(strict_types=1);

use App\Models\Answer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\travelTo;

beforeEach(function () {
    Answer::factory()->today()->create(['word' => 'tiger']);
});

test('a guess is scored letter by letter against today\'s answer', function (string $guess, array $statuses) {
    postJson('/guesses', ['guess' => $guess])
        ->assertOk()
        ->assertExactJson(['statuses' => $statuses]);
})->with([
    'a wrong guess' => ['tribe', ['correct', 'present', 'present', 'absent', 'present']],
    'a letter already correct elsewhere' => ['tight', ['correct', 'correct', 'correct', 'absent', 'absent']],
    'repeated letters' => ['geese', ['present', 'present', 'absent', 'absent', 'absent']],
    'the answer' => ['tiger', ['correct', 'correct', 'correct', 'correct', 'correct']],
    'an uppercase guess' => ['TIGER', ['correct', 'correct', 'correct', 'correct', 'correct']],
]);

test('a word that is not in the dictionary is rejected and logged', function () {
    Log::spy();

    postJson('/guesses', ['guess' => 'abcde'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['guess' => 'Not in word list']);

    Log::shouldHaveReceived('info')->once()->with('Guess not in dictionary', ['guess' => 'abcde']);
});

test('the answer is revealed only when the fifth guess misses', function () {
    foreach (['crane', 'house', 'apple', 'route'] as $guess) {
        postJson('/guesses', ['guess' => $guess])->assertJsonMissingPath('answer');
    }

    postJson('/guesses', ['guess' => 'teeth'])->assertJsonPath('answer', 'tiger');
});

test('a fifth guess that wins does not reveal the answer as a loss', function () {
    foreach (['crane', 'house', 'apple', 'route'] as $guess) {
        postJson('/guesses', ['guess' => $guess]);
    }

    postJson('/guesses', ['guess' => 'tiger'])->assertJsonMissingPath('answer');
});

test('the server counts the guesses, whatever the client says', function () {
    postJson('/guesses', ['guess' => 'crane', 'attempt' => 5])->assertJsonMissingPath('answer');
});

test('a finished Game takes no more guesses', function (array $guesses) {
    foreach ($guesses as $guess) {
        postJson('/guesses', ['guess' => $guess])->assertOk();
    }

    postJson('/guesses', ['guess' => 'tiger'])->assertConflict();
    get('/')->assertViewHas('game', fn (array $game): bool => count($game['guesses']) === count($guesses));
})->with([
    'won' => [['crane', 'tiger']],
    'lost' => [['crane', 'house', 'apple', 'route', 'teeth']],
]);

test('malformed guesses are rejected', function (array $payload) {
    postJson('/guesses', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('guess');
})->with([
    'too short' => [['guess' => 'tig']],
    'not letters' => [['guess' => 'tig3r']],
    'no guess' => [[]],
]);

test('loading the page shows the Game so far', function () {
    postJson('/guesses', ['guess' => 'tribe']);

    get('/')->assertViewHas('game', [
        'guesses' => [['word' => 'tribe', 'statuses' => ['correct', 'present', 'present', 'absent', 'present']]],
    ]);
});

test('loading the page after a lost Game shows the answer', function () {
    foreach (['crane', 'house', 'apple', 'route', 'teeth'] as $guess) {
        postJson('/guesses', ['guess' => $guess]);
    }

    get('/')->assertViewHas('game', fn (array $game): bool => $game['answer'] === 'tiger');
});

test('a guess after midnight is scored against the answer the Game started on', function () {
    travelTo(today()->setTime(23, 59));
    Answer::factory()->create(['date' => today()->addDay()->toDateString(), 'word' => 'crane']);
    get('/');

    travelTo(today()->addDay()->setTime(0, 1));

    postJson('/guesses', ['guess' => 'tiger'])->assertExactJson(['statuses' => array_fill(0, 5, 'correct')]);
});

test('the first page load after midnight starts the new day\'s Game', function () {
    travelTo(today()->setTime(23, 59));
    Answer::factory()->create(['date' => today()->addDay()->toDateString(), 'word' => 'crane']);
    postJson('/guesses', ['guess' => 'tiger']);

    travelTo(today()->addDay()->setTime(0, 1));

    get('/')->assertViewHas('game', ['guesses' => []]);
    postJson('/guesses', ['guess' => 'crane'])->assertExactJson(['statuses' => array_fill(0, 5, 'correct')]);
});

test('a guess needs the CSRF token from the page', function () {
    // Forgery protection is skipped in the testing environment, so step out of it.
    app()->detectEnvironment(fn (): string => 'local');
    $token = Str::betweenFirst((string) get('/')->getContent(), 'name="csrf-token" content="', '"');

    postJson('/guesses', ['guess' => 'crane'])->assertStatus(419);
    postJson('/guesses', ['guess' => 'crane'], ['X-CSRF-TOKEN' => $token])->assertOk();
});

test('guessing is limited to 60 a minute', function () {
    foreach (range(1, 60) as $request) {
        expect(postJson('/guesses', ['guess' => 'crane'])->status())->not->toBe(429);
    }

    postJson('/guesses', ['guess' => 'crane'])->assertTooManyRequests();
});

test('the rate limit counts each player behind Cloudflare separately', function () {
    foreach (range(1, 60) as $request) {
        expect(postJson('/guesses', ['guess' => 'crane'], ['CF-Connecting-IP' => '203.0.113.1'])->status())->not->toBe(429);
    }

    postJson('/guesses', ['guess' => 'crane'], ['CF-Connecting-IP' => '203.0.113.1'])->assertTooManyRequests();
    expect(postJson('/guesses', ['guess' => 'crane'], ['CF-Connecting-IP' => '203.0.113.2'])->status())->not->toBe(429);
});
