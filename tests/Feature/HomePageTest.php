<?php

use App\Models\Answer;

use function Pest\Laravel\get;

test('the home page renders the game without the answer', function () {
    Answer::factory()->today()->create(['word' => 'tiger']);

    get('/')
        ->assertOk()
        ->assertSee('<title>Wordis</title>', false)
        ->assertSee('x-data="game()"', false)
        ->assertSee('id="game"', false)
        ->assertSee('id="keyboard"', false)
        ->assertSee('/build/assets/app-', false)
        ->assertDontSee('tiger');
});
