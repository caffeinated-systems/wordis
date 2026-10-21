<?php

declare(strict_types=1);

use App\Models\Answer;
use App\Models\Game;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use WorkOS\Client;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\travelTo;
use function Pest\Laravel\withSession;

beforeEach(function () {
    Answer::factory()->today()->create(['word' => 'tiger']);
});

afterEach(function () {
    Client::setRequestClient(null);
});

/**
 * Play the given guesses as the current player, from the first row.
 *
 * @param  list<string>  $guesses
 */
function play(array $guesses): void
{
    foreach ($guesses as $index => $guess) {
        postJson('/guesses', ['guess' => $guess, 'row' => $index + 1])->assertOk();
    }
}

/**
 * Come back from AuthKit signed in as the given WorkOS user.
 */
function signIn(string $workosId): TestResponse
{
    workOsAuthenticates('the-code', $workosId, 'ada@example.com');

    return withSession(['state' => json_encode(['state' => 'abc'])])
        ->get('/authenticate?code=the-code&state='.urlencode(json_encode(['state' => 'abc'])));
}

test('a new account takes the guest\'s Game, and signing in returns to the finished board', function () {
    play(['crane', 'tiger']);

    signIn('user_new')->assertRedirect('/');

    expect(Game::sole()->guesses)->toBe(['crane', 'tiger']);
    get('/')->assertViewHas('game', fn (array $game): bool => array_column($game['guesses'], 'word') === ['crane', 'tiger']);
});

test('an existing account keeps its Game unless it has none or the guest finished an unfinished one', function (?array $accountGuesses, array $guestGuesses, array $kept) {
    $user = User::factory()->create(['workos_id' => 'user_existing']);
    if ($accountGuesses !== null) {
        Game::factory()->for($user)->create(['guesses' => $accountGuesses]);
    }
    play($guestGuesses);

    signIn('user_existing');

    expect($user->games()->sole()->guesses)->toBe($kept);
})->with([
    'no account Game' => [null, ['crane'], ['crane']],
    'an account Game with no guesses' => [[], ['crane'], ['crane']],
    'a finished guest Game over an unfinished account one' => [['house'], ['crane', 'tiger'], ['crane', 'tiger']],
    'an unfinished guest Game never replaces anything' => [['house'], ['crane'], ['house']],
    'a finished account Game over a finished guest one' => [['tiger'], ['crane', 'tiger'], ['tiger']],
    'a finished account Game over an unfinished guest one' => [['tiger'], ['crane'], ['tiger']],
]);

test('a guest Game with no guesses carries nothing over', function () {
    get('/');

    signIn('user_new');

    expect(Game::count())->toBe(0);
});

test('a guest Game from before midnight is saved to its own day', function () {
    travelTo(today()->setTime(23, 59));
    play(['crane']);
    $yesterday = today()->toDateString();

    travelTo(today()->addDay()->setTime(0, 1));
    Answer::factory()->today()->create(['word' => 'house']);

    signIn('user_new');

    expect(Game::sole())->date->toBe($yesterday)->guesses->toBe(['crane']);
});

test('a signed-in player\'s guesses are kept on their account', function () {
    $user = User::factory()->create();
    actingAs($user);

    get('/');
    play(['crane']);

    expect($user->games()->sole())->date->toBe(today()->toDateString())->guesses->toBe(['crane']);
});

test('a signed-in player can\'t replay a finished day from a fresh session', function () {
    $user = User::factory()->create();
    Game::factory()->for($user)->create(['guesses' => ['crane', 'tiger']]);
    actingAs($user);

    get('/')->assertViewHas('game', fn (array $game): bool => array_column($game['guesses'], 'word') === ['crane', 'tiger']);
    postJson('/guesses', ['guess' => 'tiger', 'row' => 1])->assertConflict();
    postJson('/guesses', ['guess' => 'tiger', 'row' => 3])->assertConflict();

    expect($user->games()->sole()->guesses)->toBe(['crane', 'tiger']);
});

test('a signed-in player\'s guess after midnight is scored against the answer their Game started on', function () {
    $user = User::factory()->create();
    Answer::factory()->create(['date' => today()->subDay()->toDateString(), 'word' => 'house']);
    Game::factory()->for($user)->create(['date' => today()->subDay()->toDateString(), 'guesses' => ['crane']]);
    actingAs($user);

    postJson('/guesses', ['guess' => 'house', 'row' => 2])->assertExactJson(['statuses' => array_fill(0, 5, 'correct')]);
});
