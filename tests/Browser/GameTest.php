<?php

test('the page loads without console errors', function () {
    visit('/')->assertNoSmoke();
});

test('the board has five rows of five tiles and an on-screen keyboard', function () {
    visit('/')
        ->assertCount('#game > .row', 5)
        ->assertCount('#game > .row .tile', 25)
        ->assertCount('#keyboard button.key', 28)
        ->assertPresent('#keyboard [data-key="Enter"]')
        ->assertPresent('#keyboard [data-key="Backspace"]');
});

test('typing fills the current row', function () {
    $page = visit('/');

    $page->keys('#game', ['t', 'r', 'i']);

    expect(rowLetters($page, 0))->toBe('tri');
});

test('keys that are not letters do not fill the row', function () {
    $page = visit('/');

    $page->keys('#game', ['t', '[', '_', '`', 'r']);

    expect(rowLetters($page, 0))->toBe('tr');
});

test('backspace removes the last letter', function () {
    $page = visit('/');

    $page->keys('#game', ['t', 'r', 'i', 'Backspace']);

    expect(rowLetters($page, 0))->toBe('tr');
});

test('an incomplete guess is ignored', function () {
    $page = visit('/');

    guess($page, 'tig');

    $page->assertSeeNothingIn('.message')
        ->assertPresent('#game > .row:nth-of-type(1).current');
    expect(tileStatuses($page, 0))->each->toBe('');
});

test('a word that is not in the list is rejected', function () {
    $page = visit('/');

    guess($page, 'abcde');

    $page->assertSeeIn('.message', 'Cheater, thats not a word...')
        ->assertPresent('#game > .row.current.invalid');
    expect(tileStatuses($page, 0))->each->toBe('');
});

test('a wrong guess marks the tiles and moves to the next row', function () {
    $page = visit('/');

    guess($page, 'tribe');

    expect(tileStatuses($page, 0))->toBe(['correct', 'present', 'present', 'absent', 'present']);
    $page->assertPresent('#game > .row:nth-of-type(2).current')
        ->assertSeeAnythingIn('.message')
        ->assertNoJavaScriptErrors();
});

test('a letter already marked correct elsewhere in the row is marked absent', function () {
    $page = visit('/');

    guess($page, 'tight');

    expect(tileStatuses($page, 0))->toBe(['correct', 'correct', 'correct', 'absent', 'absent']);
});

test('repeated letters are only marked present once', function () {
    $page = visit('/');

    guess($page, 'geese');

    expect(tileStatuses($page, 0))->toBe(['present', 'present', 'absent', 'absent', 'absent']);
});

test('the on-screen keyboard reflects the status of guessed letters', function () {
    $page = visit('/');

    guess($page, 'tribe');

    $page->assertAttributeContains('#keyboard [data-key="T"]', 'class', 'correct')
        ->assertAttributeContains('#keyboard [data-key="R"]', 'class', 'present')
        ->assertAttributeContains('#keyboard [data-key="B"]', 'class', 'absent');
});

test('guessing the word with the physical keyboard wins', function () {
    $page = visit('/');

    guess($page, 'tiger');

    $page->assertSeeIn('.message', 'Alright, you win')
        ->assertNoJavaScriptErrors();
    expect(tileStatuses($page, 0))->each->toBe('correct');
});

test('guessing the word with the on-screen keyboard wins', function () {
    $page = visit('/');

    foreach (['T', 'I', 'G', 'E', 'R', 'Enter'] as $key) {
        $page->click("#keyboard [data-key=\"{$key}\"]");
    }

    $page->assertSeeIn('.message', 'Alright, you win');
    expect(tileStatuses($page, 0))->each->toBe('correct');
});

test('five wrong guesses lose the game', function () {
    $page = visit('/');

    foreach (['crane', 'house', 'apple', 'route', 'teeth'] as $word) {
        guess($page, $word);
    }

    $page->assertSeeIn('.message', 'Ha ha, sucka!')
        ->assertNoJavaScriptErrors();
});

test('the page fits a phone screen without scrolling sideways', function (int $width) {
    $page = visit('/');
    $page->resize($width, 800);

    foreach (['P', 'L', 'Backspace', 'B', 'C', 'D', 'E', 'Enter'] as $key) {
        $page->click("#keyboard [data-key=\"{$key}\"]");
    }

    $page->assertSeeIn('.message', 'Cheater, thats not a word...')
        ->assertNoJavaScriptErrors();
    expect(rowLetters($page, 0))->toBe('pbcde')
        ->and($page->script('document.documentElement.scrollWidth'))->toBe($width);
})->with([360, 390]);
