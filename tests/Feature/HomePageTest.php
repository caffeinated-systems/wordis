<?php

declare(strict_types=1);

use App\Models\Answer;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('the home page renders the game without the answer', function () {
    Answer::factory()->today()->create(['word' => 'tiger']);

    get('/')
        ->assertOk()
        ->assertSee('<title>Wordis</title>', false)
        ->assertSee('x-data="game(', false)
        ->assertSee('id="game"', false)
        ->assertSee('id="keyboard"', false)
        ->assertSee('/build/assets/app-', false)
        ->assertDontSee('tiger');
});

test('the header offers a guest to log in and a signed-in player their history and to log out', function () {
    Answer::factory()->today()->create();

    get('/')->assertSee('Log in')->assertDontSee('Log out')->assertDontSee(route('history'));

    actingAs(User::factory()->create());

    get('/')->assertSee('Log out')->assertDontSee('Log in')->assertSee('href="'.route('history').'"', false);
});

test('the privacy page says what is kept and who to ask about it', function () {
    get('/privacy')
        ->assertOk()
        ->assertSee('<title>Privacy · Wordis</title>', false)
        ->assertSee('mailto:daniel.j.floris@gmail.com', false);
});

test('the footer links to the privacy page, Caffeinated Systems and Office Lurgy', function () {
    Answer::factory()->today()->create();

    get('/')
        ->assertSee('href="'.route('privacy').'"', false)
        ->assertSee('href="https://caffeinated.systems"', false)
        ->assertSee('href="https://lurgy.app"', false)
        ->assertSeeText('Also from Caffeinated Systems: Office Lurgy');
});
