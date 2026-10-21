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
                            <button type="submit">Log out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}">Log in</a>
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
        </main>
    </body>
</html>
