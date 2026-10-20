<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Api\PendingAwaitablePage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Type the given word on the physical keyboard and press Enter.
 */
function guess(PendingAwaitablePage $page, string $word): void
{
    $page->keys('#game', [...str_split($word), 'Enter']);
}

/**
 * Get the status class of each tile in the given board row.
 *
 * @return list<string>
 */
function tileStatuses(PendingAwaitablePage $page, int $rowIndex): array
{
    return $page->script(<<<JS
        Array.from(document.querySelectorAll('#game > .row')[{$rowIndex}].querySelectorAll('.tile'))
            .map((tile) => tile.classList.contains('correct') ? 'correct'
                : tile.classList.contains('present') ? 'present'
                : tile.classList.contains('absent') ? 'absent'
                : '')
    JS);
}

/**
 * Get the letters shown in the given board row.
 */
function rowLetters(PendingAwaitablePage $page, int $rowIndex): string
{
    return $page->script("document.querySelectorAll('#game > .row')[{$rowIndex}].textContent.replace(/\\s/g, '')");
}
