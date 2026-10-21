<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Exceptions;
use WorkOS\Client;
use WorkOS\Exception\BadRequestException;
use WorkOS\RequestClient\RequestClientInterface;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

afterEach(function () {
    Client::setRequestClient(null);
});

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

test('a guest is sent to AuthKit to log in', function () {
    get('/login')
        ->assertRedirectContains('https://api.workos.com/user_management/authorize')
        ->assertRedirectContains('client_id=client_test')
        ->assertSessionHas('state');
});

test('the callback registers and signs in a new player', function () {
    workOsAuthenticates('the-code', 'user_new', 'ada@example.com');

    withSession(['state' => json_encode(['state' => 'abc'])])
        ->get('/authenticate?code=the-code&state='.urlencode(json_encode(['state' => 'abc'])))
        ->assertRedirect('/');

    $user = User::sole();
    expect($user->workos_id)->toBe('user_new')
        ->and($user->email)->toBe('ada@example.com')
        ->and($user->password)->toBeNull();
    assertAuthenticatedAs($user);
});

test('the callback signs in an existing player by their WorkOS id', function () {
    $user = User::factory()->create(['workos_id' => 'user_existing', 'email' => 'old@example.com']);
    workOsAuthenticates('the-code', 'user_existing', 'new@example.com');

    withSession(['state' => json_encode(['state' => 'abc'])])
        ->get('/authenticate?code=the-code&state='.urlencode(json_encode(['state' => 'abc'])))
        ->assertRedirect('/');

    assertDatabaseCount('users', 1);
    assertAuthenticatedAs($user);
});

test('a WorkOS error at the callback is reported and the player is sent home as a guest', function () {
    Exceptions::fake();
    $requestClient = Mockery::mock(RequestClientInterface::class);
    $requestClient->shouldReceive('request')->once()->andReturn([json_encode(['error' => 'invalid_grant']), [], 400]);
    Client::setRequestClient($requestClient);

    withSession(['state' => json_encode(['state' => 'abc'])])
        ->get('/authenticate?code=expired&state='.urlencode(json_encode(['state' => 'abc'])))
        ->assertRedirect('/');

    assertGuest();
    Exceptions::assertReported(BadRequestException::class);
});

test('the callback refuses a state that does not match the session', function () {
    withSession(['state' => json_encode(['state' => 'abc'])])
        ->get('/authenticate?code=the-code&state='.urlencode(json_encode(['state' => 'forged'])))
        ->assertForbidden();

    assertGuest();
});

test('the callback refuses a code when no login is pending', function () {
    get('/authenticate?code=attackers-code')->assertForbidden();

    assertGuest();
});

test('the callback refuses a state that is not a string', function () {
    withSession(['state' => json_encode(['state' => 'abc'])])
        ->get('/authenticate?code=the-code&state[]=abc')
        ->assertForbidden();

    assertGuest();
});

test('a player whose email another player used to have can sign up', function () {
    User::factory()->create(['workos_id' => 'user_moved_on', 'email' => 'ada@example.com']);
    workOsAuthenticates('the-code', 'user_new', 'ada@example.com');

    withSession(['state' => json_encode(['state' => 'abc'])])
        ->get('/authenticate?code=the-code&state='.urlencode(json_encode(['state' => 'abc'])))
        ->assertRedirect('/');

    assertAuthenticatedAs(User::firstWhere('workos_id', 'user_new'));
});

test('a player who cancels on AuthKit is sent home without asking WorkOS for a code', function () {
    $requestClient = Mockery::mock(RequestClientInterface::class);
    $requestClient->shouldNotReceive('request');
    Client::setRequestClient($requestClient);

    withSession(['state' => json_encode(['state' => 'abc'])])
        ->get('/authenticate?error=access_denied&state='.urlencode(json_encode(['state' => 'abc'])))
        ->assertRedirect('/');

    assertGuest();
});

test('logging out signs the player out', function () {
    actingAs(User::factory()->create());

    post('/logout')->assertRedirect('/');

    assertGuest();
});

test('a guest whose session already ended is sent home by log out', function () {
    post('/logout')->assertRedirect('/');

    assertGuest();
});
