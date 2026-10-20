<?php

use App\Models\Answer;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\postJson;

beforeEach(function () {
    Answer::factory()->today()->create(['word' => 'tiger']);
});

test('a guess is scored letter by letter against today\'s answer', function (string $guess, array $statuses) {
    postJson('/guesses', ['guess' => $guess, 'attempt' => 1])
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

    postJson('/guesses', ['guess' => 'abcde', 'attempt' => 1])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['guess' => 'Not in word list']);

    Log::shouldHaveReceived('info')->once()->with('Guess not in dictionary', ['guess' => 'abcde']);
});

test('the answer is revealed only when the last guess misses', function () {
    postJson('/guesses', ['guess' => 'crane', 'attempt' => 4])->assertJsonMissingPath('answer');
    postJson('/guesses', ['guess' => 'tiger', 'attempt' => 5])->assertJsonMissingPath('answer');
    postJson('/guesses', ['guess' => 'crane', 'attempt' => 5])->assertJsonPath('answer', 'tiger');
});

test('malformed guesses are rejected', function (array $payload, string $field) {
    postJson('/guesses', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor($field);
})->with([
    'too short' => [['guess' => 'tig', 'attempt' => 1], 'guess'],
    'not letters' => [['guess' => 'tig3r', 'attempt' => 1], 'guess'],
    'no attempt' => [['guess' => 'tiger'], 'attempt'],
    'attempt past the last guess' => [['guess' => 'tiger', 'attempt' => 6], 'attempt'],
]);

test('guessing is limited to 60 a minute', function () {
    foreach (range(1, 60) as $request) {
        postJson('/guesses', ['guess' => 'crane', 'attempt' => 1])->assertOk();
    }

    postJson('/guesses', ['guess' => 'crane', 'attempt' => 1])->assertTooManyRequests();
});

test('the rate limit counts each player behind Cloudflare separately', function () {
    foreach (range(1, 60) as $request) {
        postJson('/guesses', ['guess' => 'crane', 'attempt' => 1], ['CF-Connecting-IP' => '203.0.113.1'])->assertOk();
    }

    postJson('/guesses', ['guess' => 'crane', 'attempt' => 1], ['CF-Connecting-IP' => '203.0.113.1'])->assertTooManyRequests();
    postJson('/guesses', ['guess' => 'crane', 'attempt' => 1], ['CF-Connecting-IP' => '203.0.113.2'])->assertOk();
});

test('a guess starts no session', function () {
    postJson('/guesses', ['guess' => 'crane', 'attempt' => 1])
        ->assertOk()
        ->assertCookieMissing(config('session.cookie'));
});
