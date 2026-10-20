<?php

use App\Models\Answer;

use function Pest\Laravel\get;

test('the home page renders the game', function () {
    Answer::factory()->today()->create(['word' => 'tiger']);

    get('/')
        ->assertOk()
        ->assertSee('<title>Wordis</title>', false)
        ->assertSee('x-data="game(\'tiger\')"', false)
        ->assertSee('id="game"', false)
        ->assertSee('id="keyboard"', false)
        ->assertSee('/build/assets/app-', false);
});
