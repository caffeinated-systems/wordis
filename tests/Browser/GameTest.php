<?php

declare(strict_types=1);

use App\Models\Answer;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Answer::factory()->today()->create(['word' => 'tiger']);
});

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

test('enter on a focused link does not submit the guess', function () {
    $page = visit('/');
    $page->keys('#game', ['t', 'i', 'g', 'e', 'r']);

    $page->script("document.querySelector('footer a').dispatchEvent(new KeyboardEvent('keyup', { key: 'Enter', bubbles: true }))");
    settle($page);

    expect(tileStatuses($page, 0))->toBe(['', '', '', '', '']);
});

test('clicking a footer link does not leave it focused to catch the next enter', function () {
    $page = visit('/');

    expect($page->script(<<<'JS'
        (() => {
            const press = new MouseEvent('mousedown', { bubbles: true, cancelable: true });
            document.querySelector('footer a').dispatchEvent(press);
            return press.defaultPrevented;
        })()
    JS))->toBeTrue();
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

    $page->assertSeeNothingIn('.message span')
        ->assertPresent('#game > .row:nth-of-type(1).current');
    expect(tileStatuses($page, 0))->each->toBe('')
        ->and($page->script("getComputedStyle(document.querySelector('.message')).opacity"))->toBe('0');
});

test('a word that is not in the list is rejected', function () {
    $page = visit('/');

    guess($page, 'abcde');

    $page->assertSeeIn('.message', 'Not in word list')
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

    $page->assertSeeIn('.message', 'Lucky')
        ->assertNoJavaScriptErrors();
    expect(tileStatuses($page, 0))->each->toBe('correct');
});

test('a wrong guess gets a diss that matches how close it was', function (string $word, string $heat) {
    $page = visit('/');
    $page->script('Math.random = () => 0');

    guess($page, $word);

    expect($page->script("Alpine.\$data(document.querySelector('main')).disses.{$heat}.includes(document.querySelector('.message span').textContent)"))->toBeTrue();
})->with([
    'nothing right is cold' => ['plush', 'cold'],
    'four letters in place is hot' => ['timer', 'hot'],
]);

test('a guest who finishes a Game is offered an account to save it', function () {
    $page = visit('/');

    guess($page, 'tiger');

    $page->assertSeeIn('#finished', 'Create an account to save this result')
        ->assertAttributeContains('.save-result', 'href', '/login?signup=1');
});

test('a signed-in player who finishes a Game is not offered an account', function () {
    actingAs(User::factory()->create());
    $page = visit('/');

    guess($page, 'tiger');

    $page->assertSeeIn('.message', 'Lucky')
        ->assertDontSee('Create an account to save this result');
});

test('a finished Game is copied to share where there is no share sheet', function () {
    $page = visit('/');
    $page->script("Object.defineProperty(navigator, 'share', { value: undefined }); Object.defineProperty(navigator, 'clipboard', { value: { writeText: async (text) => { window.shared = text; } } })");

    guess($page, 'crane');
    guess($page, 'tiger');
    $page->click('.share');

    $page->assertSeeIn('.share', 'Copied');
    expect($page->script('window.shared'))->toMatch('/^Wordis '.today()->format('j M')." · 2\\/5 · ⏱ 0:0\\d\n\"Show-off\"\n⬛🟨⬛⬛🟨\n🟩🟩🟩🟩🟩\nhttp:\\/\\/[^\n]+\\z/u");
});

test('a share that fails says so rather than doing nothing', function () {
    $page = visit('/');
    $page->script("navigator.share = async () => { throw new DOMException('Blocked', 'NotAllowedError'); }; Object.defineProperty(navigator, 'clipboard', { value: { writeText: async () => { throw new DOMException('Denied', 'NotAllowedError'); } } }); null");

    guess($page, 'tiger');
    $page->click('.share');

    $page->assertSeeIn('.share', "Couldn't share");
});

test('a lost Game is shared through the share sheet without its answer', function () {
    $page = visit('/');
    $page->script('navigator.share = async ({ text }) => { window.shared = text; }; null');

    foreach (['crane', 'house', 'apple', 'route', 'teeth'] as $word) {
        guess($page, $word);
    }
    $page->script("Alpine.\$data(document.querySelector('main')).seconds = 3725");
    $page->click('.share');

    expect($page->script('window.shared'))
        ->toContain(' · X/5 · ⏱ 1:02:05')
        ->toContain('"Ha ha, sucka!"')
        ->not->toContain('TIGER');
});

test('a finished Game swaps the keyboard for sharing, and the message bubble keeps only the message', function () {
    $page = visit('/');

    guess($page, 'tiger');

    $page->assertVisible('#finished .share')
        ->assertMissing('.message .share');
    expect($page->script("getComputedStyle(document.querySelector('#keyboard')).visibility"))->toBe('hidden');
});

test('a later win gets its own message', function () {
    $page = visit('/');

    guess($page, 'crane');
    guess($page, 'tiger');

    $page->assertSeeIn('.message', 'Show-off')
        ->assertNoJavaScriptErrors();
});

test('guessing the word with the on-screen keyboard wins', function () {
    $page = visit('/');

    foreach (['T', 'I', 'G', 'E', 'R', 'Enter'] as $key) {
        $page->click("#keyboard [data-key=\"{$key}\"]");
    }
    settle($page);

    $page->assertSeeIn('.message', 'Lucky');
    expect(tileStatuses($page, 0))->each->toBe('correct');
});

test('five wrong guesses lose the game', function () {
    $page = visit('/');

    foreach (['crane', 'house', 'apple', 'route', 'teeth'] as $word) {
        guess($page, $word);
    }

    $page->assertSeeIn('.message', 'Ha ha, sucka! It was TIGER')
        ->assertNoJavaScriptErrors();
    expect($page->script("getComputedStyle(document.querySelector('.message')).color"))->toBe('rgb(247, 247, 73)');
});

test('a lost game cannot be replayed into a win', function () {
    $page = visit('/');

    foreach (['crane', 'house', 'apple', 'route', 'teeth'] as $word) {
        guess($page, $word);
    }
    $page->keys('#game', array_fill(0, 5, 'Backspace'));
    guess($page, 'tiger');

    $page->assertSeeIn('.message', 'Ha ha, sucka! It was TIGER');
    expect(rowLetters($page, 4))->toBe('teeth');
});

test('reloading mid-Game shows the guesses so far', function () {
    $page = visit('/');

    guess($page, 'tribe');
    $page->refresh();

    expect(rowLetters($page, 0))->toBe('tribe')
        ->and(tileStatuses($page, 0))->toBe(['correct', 'present', 'present', 'absent', 'present']);
    $page->assertPresent('#game > .row:nth-of-type(2).current')
        ->assertSeeNothingIn('.message span')
        ->assertNoJavaScriptErrors();
});

test('reloading a finished Game shows it finished', function () {
    $page = visit('/');

    foreach (['crane', 'house', 'apple', 'route', 'teeth'] as $word) {
        guess($page, $word);
    }
    $page->refresh();
    guess($page, 'tiger');

    $page->assertSeeIn('.message', 'Ha ha, sucka! It was TIGER')
        ->assertNoJavaScriptErrors();
    expect(rowLetters($page, 4))->toBe('teeth');
});

test('a guess for a Game another tab moved on asks for a reload', function () {
    $page = visit('/');

    $page->script("fetch('/guesses', { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ guess: 'crane', row: 1 }) })");
    guess($page, 'house');

    $page->assertSeeIn('.message', 'This game moved on, reload the page')
        ->assertNoJavaScriptErrors();
    expect(tileStatuses($page, 0))->each->toBe('');
});

test('a won game ignores further keys', function () {
    $page = visit('/');

    guess($page, 'tiger');
    $page->keys('#game', ['Backspace']);
    guess($page, 'x');

    $page->assertSeeIn('.message', 'Lucky');
    expect(rowLetters($page, 0))->toBe('tiger');
});

test('the page fits a phone screen without scrolling sideways', function (int $width) {
    $page = visit('/');
    $page->resize($width, 800);

    foreach (['P', 'L', 'Backspace', 'B', 'C', 'D', 'E'] as $key) {
        $page->click("#keyboard [data-key=\"{$key}\"]");
    }
    $boardTop = $page->script("document.querySelector('#game').getBoundingClientRect().top");
    $page->click('#keyboard [data-key="Enter"]');
    settle($page);

    $page->assertSeeIn('.message', 'Not in word list')
        ->assertNoJavaScriptErrors();
    expect(rowLetters($page, 0))->toBe('pbcde')
        ->and($page->script('document.documentElement.scrollWidth'))->toBe($width)
        ->and($page->script("document.querySelector('#game').getBoundingClientRect().top"))->toBe($boardTop);
})->with([320, 360, 390]);

test('the board and keyboard fit on a phone screen without scrolling', function (int $width, int $height) {
    $page = visit('/');
    $page->resize($width, $height);

    expect($page->script("document.querySelector('footer').getBoundingClientRect().bottom"))->toBeLessThanOrEqual($height)
        ->and($page->script('document.documentElement.scrollHeight'))->toBeLessThanOrEqual($height)
        ->and($page->script("document.querySelector('#wordis-logo').getBoundingClientRect().top"))->toBeGreaterThanOrEqual(0)
        ->and($page->script("document.querySelector('#game .tile').getBoundingClientRect().height"))->toBeGreaterThanOrEqual(50);
})->with([
    'small phone' => [360, 640],
    'large phone' => [390, 844],
]);

test('a screen too short for the board scrolls instead of squashing it', function () {
    $page = visit('/');
    $page->resize(640, 360);

    expect($page->script("document.querySelector('#game .tile').getBoundingClientRect().height"))->toBeGreaterThanOrEqual(50)
        ->and($page->script("document.querySelector('#wordis-logo').getBoundingClientRect().top"))->toBeGreaterThanOrEqual(0)
        ->and($page->script('document.documentElement.scrollHeight'))
        ->toBeGreaterThanOrEqual($page->script("Math.floor(document.querySelector('#keyboard').getBoundingClientRect().bottom)"));
});

test('a laptop screen keeps the board readable', function () {
    $page = visit('/');
    $page->resize(1366, 650);

    expect($page->script("document.querySelector('#game .tile').getBoundingClientRect().height"))->toBeGreaterThanOrEqual(60)
        ->and($page->script("document.querySelector('footer').getBoundingClientRect().bottom"))->toBeLessThanOrEqual(650);
});

test('tiles keep their full size on a tall desktop screen', function () {
    $page = visit('/');
    $page->resize(1280, 1080);

    expect($page->script("document.querySelector('#game .tile').getBoundingClientRect().height"))->toBe(100);
});

test('pressing Enter after tapping an on-screen key submits the guess', function () {
    $page = visit('/');

    foreach (['T', 'I', 'G', 'E', 'R', 'Backspace'] as $key) {
        $page->click("#keyboard [data-key=\"{$key}\"]");
    }
    guess($page, 'r');

    $page->assertSeeIn('.message', 'Lucky')
        ->assertNoJavaScriptErrors();
});

test('pressing Enter on a focused on-screen key only presses that key', function () {
    $page = visit('/');

    $page->keys('#game', ['t', 'i', 'g', 'e']);
    $page->keys('#keyboard [data-key="R"]', ['Enter']);

    expect(rowLetters($page, 0))->toBe('tiger');
    $page->assertSeeNothingIn('.message span')
        ->assertPresent('#game > .row:nth-of-type(1).current')
        ->assertNoJavaScriptErrors();
});

test('finishing from a focused on-screen key moves focus to Share', function () {
    $page = visit('/');

    $page->keys('#game', ['t', 'i', 'g', 'e', 'r']);
    $page->keys('#keyboard [data-key="Enter"]', ['Enter']);
    settle($page);

    expect($page->script("document.activeElement.classList.contains('share')"))->toBeTrue();
});

test('pressing Enter after sliding off an on-screen key submits the guess', function () {
    $page = visit('/');

    $page->keys('#game', ['t', 'i', 'g', 'e']);
    $page->drag('#keyboard [data-key="Backspace"]', '#game');
    guess($page, 'r');

    $page->assertSeeIn('.message', 'Lucky')
        ->assertNoJavaScriptErrors();
});

test('the message colour shows what happened', function () {
    $page = visit('/');
    $messageColour = fn () => $page->script("getComputedStyle(document.querySelector('.message')).color");

    guess($page, 'abcde');
    expect($messageColour())->toBe('rgb(255, 92, 92)');

    $page->keys('#game', array_fill(0, 5, 'Backspace'));
    guess($page, 'crane');
    expect($messageColour())->toBe('rgb(247, 247, 73)');

    guess($page, 'tiger');
    expect($messageColour())->toBe('rgb(71, 215, 71)');
    $page->assertNoJavaScriptErrors();
});

test('the message sits beside the board on desktop', function () {
    $page = visit('/');
    $page->resize(1280, 800);

    guess($page, 'crane');

    $bubble = $page->script("JSON.parse(JSON.stringify(document.querySelector('.message').getBoundingClientRect()))");
    $firstRow = $page->script("JSON.parse(JSON.stringify(document.querySelector('#game > .row').getBoundingClientRect()))");
    expect($bubble['left'])->toBeGreaterThan($firstRow['right'])
        ->and($bubble['top'])->toBeGreaterThanOrEqual($firstRow['top'])
        ->and($bubble['top'])->toBeLessThan($firstRow['bottom'])
        ->and($page->script('document.documentElement.scrollWidth'))->toBe(1280);
});

test('the message sits between the board and the keyboard on phones', function () {
    $page = visit('/');
    $page->resize(320, 700);
    $keyboardTop = $page->script("document.querySelector('#keyboard').getBoundingClientRect().top");

    guess($page, 'abcde');

    $bubble = $page->script("JSON.parse(JSON.stringify(document.querySelector('.message').getBoundingClientRect()))");
    expect($bubble['top'])->toBeGreaterThan($page->script("document.querySelector('#game').getBoundingClientRect().bottom"))
        ->and($bubble['bottom'])->toBeLessThan($page->script("document.querySelector('#keyboard').getBoundingClientRect().top"))
        ->and($page->script("document.querySelector('#keyboard').getBoundingClientRect().top"))->toBe($keyboardTop)
        ->and($page->script('document.documentElement.scrollWidth'))->toBe(320);
});

test('the header icons sit in the page corners, clear of the logo and the board', function (bool $signedIn, int $width, int $height) {
    if ($signedIn) {
        actingAs(User::factory()->create());
    }
    $page = visit('/');
    $page->resize($width, $height);

    // History takes the top-left corner and the account icon the top-right.
    expect($page->script(<<<'JS'
        (() => {
            const icons = [...document.querySelectorAll('.account a, .account button')];
            const logo = document.querySelector('#wordis-logo').getBoundingClientRect();
            const boardTop = document.querySelector('#game').getBoundingClientRect().top;
            const width = document.documentElement.clientWidth;

            return icons.map((icon, index) => {
                const box = icon.getBoundingClientRect();
                const isAccount = index === icons.length - 1;

                return document.elementFromPoint(box.left + box.width / 2, box.top + box.height / 2).closest('.account a, .account button') === icon
                    && (isAccount ? box.left >= logo.right && box.right === width - 16 : box.right <= logo.left && box.left === 16)
                    && box.top === 16
                    && box.bottom <= boardTop - 7;
            });
        })()
    JS))->toBe($signedIn ? [true, true] : [true]);
})->with(['signed out' => false, 'signed in' => true])->with([
    'small phone' => [320, 640],
    'phone' => [390, 844],
    'laptop' => [1366, 650],
]);
