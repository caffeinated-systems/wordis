<?php

declare(strict_types=1);

use App\Models\Answer;
use App\Models\Game;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;

beforeEach(function () {
    travelTo(today()->setDate(2026, 10, 21));
});

/**
 * Give the user a Game on the given date, where a "tiger" guess stands for that day's answer. Returns the answer.
 *
 * @param  list<string>  $guesses
 */
function playedOn(User $user, string $date, array $guesses): string
{
    $answer = Answer::factory()->create(['date' => $date, 'word' => fake()->unique()->randomElement(array_diff(Answer::pool(), ['crane', 'house']))])->word;
    Game::factory()->for($user)->create(['date' => $date, 'guesses' => array_map(fn (string $guess): string => $guess === 'tiger' ? $answer : $guess, $guesses)]);

    return $answer;
}

test('guests are sent to sign in', function () {
    get('/history')->assertRedirect(route('login'));
});

test('stats count finished days in a row, a loss included', function () {
    $user = User::factory()->create();
    playedOn($user, '2026-10-14', []);
    playedOn($user, '2026-10-15', ['tiger']);
    playedOn($user, '2026-10-16', ['crane', 'crane', 'crane', 'crane', 'crane']);
    playedOn($user, '2026-10-17', ['crane', 'house', 'tiger']);
    playedOn($user, '2026-10-19', ['crane', 'tiger']);
    playedOn($user, '2026-10-20', ['crane', 'crane', 'crane', 'crane', 'crane']);
    playedOn($user, '2026-10-21', ['crane']);

    actingAs($user)->get('/history')->assertOk()->assertViewHas('stats', [
        'played' => 6,
        'winRate' => 60,
        'currentStreak' => 2,
        'bestStreak' => 3,
        'distribution' => [1 => 1, 2 => 1, 3 => 1, 4 => 0, 5 => 0, 'lost' => 2],
    ]);
});

test('today\'s unfinished Game doesn\'t lower the win rate', function () {
    $user = User::factory()->create();
    playedOn($user, '2026-10-20', ['tiger']);
    playedOn($user, '2026-10-21', ['crane']);

    actingAs($user)->get('/history')->assertViewHas('stats', fn (array $stats): bool => $stats['played'] === 2 && $stats['winRate'] === 100);
});

test('a player with no Games is told how to fill the page instead of seeing empty stats', function () {
    actingAs(User::factory()->create())->get('/history')
        ->assertOk()
        ->assertViewHas('previousMonth', null)
        ->assertSee('Your games will show up here once you\'ve played', false)
        ->assertDontSee('Best streak')
        ->assertSee('21 October: still to play', false)
        ->assertDontSee('class="day missed"', false);
});

test('only days after the first Game count as missed', function () {
    $user = User::factory()->create();
    playedOn($user, '2026-10-10', ['tiger']);

    actingAs($user)->get('/history')
        ->assertSee('class="day future" style="--order: 11"', false)
        ->assertSee('class="day missed" style="--order: 13"', false);
});

test('the current streak ends at a day without a finished Game', function (array $games, int $currentStreak) {
    $user = User::factory()->create();
    foreach ($games as $date => $guesses) {
        playedOn($user, $date, $guesses);
    }

    actingAs($user)->get('/history')->assertViewHas('stats', fn (array $stats): bool => $stats['currentStreak'] === $currentStreak);
})->with([
    'today finished' => [['2026-10-20' => ['tiger'], '2026-10-21' => ['tiger']], 2],
    'yesterday unfinished' => [['2026-10-19' => ['tiger'], '2026-10-20' => ['crane']], 0],
    'yesterday missed' => [['2026-10-19' => ['tiger']], 0],
    'yesterday opened but not played' => [['2026-10-19' => ['tiger'], '2026-10-20' => []], 0],
]);

test('today\'s answer stays hidden until today\'s Game is finished', function () {
    $user = User::factory()->create();
    $yesterday = playedOn($user, '2026-10-20', ['crane']);
    $today = playedOn($user, '2026-10-21', ['crane']);

    actingAs($user)->get('/history')
        ->assertViewHas('cells', fn (array $cells): bool => collect($cells)->filter()->pluck('game.answer', 'date')->only(['2026-10-20', '2026-10-21'])->all() === ['2026-10-20' => $yesterday, '2026-10-21' => null])
        ->assertDontSee($today);
});

test('months run from the first played Game to this month', function (string $query, string $shown, ?string $previous, ?string $next) {
    $user = User::factory()->create();
    playedOn($user, '2026-08-31', ['tiger']);

    actingAs($user)->get("/history{$query}")
        ->assertViewHas('month', fn ($month): bool => $month->format('Y-m') === $shown)
        ->assertViewHas('previousMonth', $previous)
        ->assertViewHas('nextMonth', $next);
})->with([
    'this month by default' => ['', '2026-10', '2026-09', null],
    'the first month' => ['?month=2026-08', '2026-08', null, '2026-09'],
    'before the first month' => ['?month=2026-07', '2026-10', '2026-09', null],
    'after this month' => ['?month=2026-11', '2026-10', '2026-09', null],
    'not a month' => ['?month=2026-13', '2026-10', '2026-09', null],
    'not a string' => ['?month[]=2026-09', '2026-10', '2026-09', null],
]);
