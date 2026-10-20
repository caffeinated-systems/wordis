<?php

declare(strict_types=1);

use App\Models\Answer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\travel;
use function Pest\Laravel\travelTo;

test('the first visit of the day stores a word from the answer pool', function () {
    $word = Answer::today();

    expect(Answer::pool())->toContain($word);
    assertDatabaseHas('answers', ['date' => today()->toDateString(), 'word' => $word]);
});

test('later visits on the same day get the same answer without reading the database', function () {
    $word = Answer::today();

    DB::enableQueryLog();
    expect(Answer::today())->toBe($word)
        ->and(DB::getQueryLog())->toBeEmpty();

    Cache::store(config('cache.answer_store'))->flush();

    expect(Answer::today())->toBe($word);
    assertDatabaseCount('answers', 1);
});

test('the next day gets an answer that has not been used before', function () {
    $first = Answer::today();
    travel(1)->day();

    expect(Answer::today())->not->toBe($first);
    assertDatabaseCount('answers', 2);
});

test('an answer already stored for today is used as it is', function () {
    Answer::factory()->today()->create(['word' => 'tiger']);

    expect(Answer::today())->toBe('tiger');
    assertDatabaseCount('answers', 1);
});

test('the day changes at midnight Sydney time', function () {
    travelTo(now('UTC')->setDate(2026, 10, 20)->setTime(13, 30));

    Answer::today();

    assertDatabaseHas('answers', ['date' => '2026-10-21']);
});

test('a used up answer pool is reported rather than repeating a word', function () {
    Answer::insert(array_map(
        fn (string $word, int $day): array => ['date' => today()->subDays($day + 1)->toDateString(), 'word' => $word],
        Answer::pool(),
        array_keys(Answer::pool()),
    ));

    Answer::today();
})->throws(RuntimeException::class, 'The answer pool has no unused words left.');
