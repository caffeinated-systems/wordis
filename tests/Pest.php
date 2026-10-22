<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Api\PendingAwaitablePage;
use Tests\TestCase;
use WorkOS\Client;
use WorkOS\RequestClient\RequestClientInterface;

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
 *
 * Pins Math.random first, so the Game picks the first line of each message list.
 */
function guess(PendingAwaitablePage $page, string $word): void
{
    $page->script('Math.random = () => 0');
    $page->keys('#game', [...str_split($word), 'Enter']);
    settle($page);
}

/**
 * Wait until the guess sent to the server has been scored.
 */
function settle(PendingAwaitablePage $page): void
{
    $page->script("Alpine.\$data(document.querySelector('main')).pending");
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

/**
 * Answer WorkOS's code exchange for the given code with the given WorkOS user.
 */
function workOsAuthenticates(string $code, string $workosId, string $email): void
{
    $requestClient = Mockery::mock(RequestClientInterface::class);
    $requestClient->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $url, ?array $headers, ?array $params): bool => $url === 'https://api.workos.com/user_management/authenticate'
            && $params['code'] === $code)
        ->andReturn([json_encode([
            'user' => [
                'object' => 'user',
                'id' => $workosId,
                'email' => $email,
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'email_verified' => true,
                'profile_picture_url' => null,
                'created_at' => '2026-10-21T00:00:00.000Z',
                'updated_at' => '2026-10-21T00:00:00.000Z',
            ],
            'organization_id' => null,
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
        ]), [], 200]);

    Client::setRequestClient($requestClient);
}
