<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Wordis</title>
        <meta property="og:type" content="website">
        <meta property="og:title" content="Wordis">
        <meta property="og:description" content="Find the day's five-letter word in five guesses.">
        <meta property="og:url" content="{{ url('/') }}">
        <meta property="og:image" content="{{ asset('assets/og.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <link rel="icon" href="/favicon.ico" sizes="32x32">
        <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
    </head>
    <body>
        <main  x-data="game(@js($game))" @keyup.window="onPhysicalKey($event)" >
            <header>
                @include('partials.account')
                <h1 aria-label="Wordis">
                    <img id="wordis-logo" src='/assets/wordis-logo.svg' alt='Wordis' width="4460" height="995" />
                </h1>
            </header>
            <div id="game">
                <output class="message" :class="{ 'invalid': errors, 'correct': won }">
                    <span x-text="message"></span>
                    <template x-if="state === 'complete' && !errors">
                        <button class="share" type="button" @click="share()" x-text="shareLabel"></button>
                    </template>
                    @guest
                        <template x-if="state === 'complete' && !errors">
                            <a class="save-result" href="{{ route('login', ['signup' => 1]) }}">Create an account to save this result</a>
                        </template>
                    @endguest
                </output>
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
