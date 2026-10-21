<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Wordis</title>
        <link rel="icon" href="/favicon.ico" sizes="32x32">
        <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
    </head>
    <body>
        <main  x-data="game()" @keyup.window="onPhysicalKey($event)" >
            <header>
                <nav class="account">
                    @auth
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="signed-in" aria-label="Log out" title="Log out"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg></button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" aria-label="Log in" title="Log in"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg></a>
                    @endauth
                </nav>
                <h1 aria-label="Wordis">
                    <img id="wordis-logo" src='/assets/wordis-logo.svg' alt='Wordis' width="4460" height="995" />
                </h1>
            </header>
            <div id="game">
                <output class="message" :class="{ 'invalid': errors, 'correct': won }" x-text="message"></output>
                <template x-for="(row, index) in board">
                    <div class="row" :class="{ 'current': currentRowIndex === index, 'invalid': currentRowIndex === index && errors}">
                        <template x-for="tile in row">
                            <div class="tile" :class="tile.status" x-text="tile.letter"></div>
                        </template>
                    </div>
                </template>
            </div>
            <div id="keyboard"  @click.stop="onScreenKey($event)" @mousedown="keepFocusOffKeys($event)">
                <template x-for="row in letters">
                    <div class="row">
                        <template x-for="key in row">
                            <button
                                class="key"
                                :class="matchingTileForKey(key)?.status"
                                type="button"
                                :data-key="key"
                                :aria-label="key"
                                x-text="key === 'Backspace' ? '⌫' : key">
                            </button>
                        </template>
                    </div>
                </template>
            </div>
            <footer @mousedown="keepFocusOffKeys($event)">
                <a href="{{ route('privacy') }}">Privacy</a>
                <span aria-hidden="true">·</span>
                Built with <span role="img" aria-label="love">♥</span> by <a href="https://caffeinated.systems" target="_blank" rel="noopener">Caffeinated Systems</a>
            </footer>
        </main>
    </body>
</html>
