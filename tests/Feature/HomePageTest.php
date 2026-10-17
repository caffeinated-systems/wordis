<?php

use function Pest\Laravel\get;

test('the home page renders the game', function () {
    get('/')
        ->assertOk()
        ->assertSee('<title>Wordis</title>', false)
        ->assertSee('x-data="game"', false)
        ->assertSee('id="game"', false)
        ->assertSee('id="keyboard"', false)
        ->assertSee('/build/assets/app-', false);
});
