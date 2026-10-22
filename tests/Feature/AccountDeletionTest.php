<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\Exceptions;
use WorkOS\Client;
use WorkOS\Exception\ServerException;
use WorkOS\RequestClient\RequestClientInterface;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\delete;

afterEach(function () {
    Client::setRequestClient(null);
});

/**
 * Answer WorkOS's delete-user request for the given id with the given status code.
 */
function workOsDeletes(string $workosId, int $status): void
{
    $requestClient = Mockery::mock(RequestClientInterface::class);
    $requestClient->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $url): bool => $method === 'delete' && $url === "https://api.workos.com/user_management/users/{$workosId}")
        ->andReturn(['', [], $status]);

    Client::setRequestClient($requestClient);
}

test('a player deletes their WorkOS login, their account and their Games, and is signed out', function () {
    $user = User::factory()->create(['workos_id' => 'user_leaving']);
    $games = Game::factory()->for($user)->count(2)->sequence(['date' => '2026-10-20'], ['date' => '2026-10-21'])->create();
    $otherGame = Game::factory()->for(User::factory())->create();
    workOsDeletes('user_leaving', 202);

    actingAs($user)->delete('/account')
        ->assertRedirect('/')
        ->assertSessionHas('status', 'Your account was deleted.');

    assertGuest();
    assertModelMissing($user);
    $games->each(fn (Game $game) => assertModelMissing($game));
    assertModelExists($otherGame);
});

test('a player whose WorkOS login is already gone still has their account deleted', function () {
    $user = User::factory()->create(['workos_id' => 'user_gone']);
    workOsDeletes('user_gone', 404);

    actingAs($user)->delete('/account')->assertRedirect('/');

    assertGuest();
    assertModelMissing($user);
});

test('a WorkOS failure keeps the account and its Games, and sends the player back to try again', function () {
    Exceptions::fake();
    $user = User::factory()->create(['workos_id' => 'user_staying']);
    $game = Game::factory()->for($user)->create();
    workOsDeletes('user_staying', 500);

    actingAs($user)->delete('/account')
        ->assertRedirect(route('history'))
        ->assertSessionHas('error');

    assertModelExists($user);
    assertModelExists($game);
    Exceptions::assertReported(ServerException::class);
});

test('a guest cannot delete an account', function () {
    $requestClient = Mockery::mock(RequestClientInterface::class);
    $requestClient->shouldNotReceive('request');
    Client::setRequestClient($requestClient);

    delete('/account')->assertRedirect();

    assertModelExists(User::factory()->create());
});
