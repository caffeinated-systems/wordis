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
        {{-- The countdown's font is fetched up front, so a finished Game's readout isn't blank while it loads. --}}
        <link rel="preload" href="{{ Vite::asset('resources/fonts/DSEG7Classic-Italic.woff2') }}" as="font" type="font/woff2" crossorigin>
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
            @session('status')
                <p class="notice" role="status">{{ $value }}</p>
            @endsession
            <div id="game">
                <output id="message" class="message" :class="{ 'invalid': errors, 'correct': won }">
                    <span x-text="message"></span>
                </output>
                <template x-for="(row, index) in board">
                    <div class="row" :class="{ 'current': currentRowIndex === index, 'invalid': currentRowIndex === index && errors}">
                        <template x-for="tile in row">
                            <div class="tile" :class="tile.status" x-text="tile.letter"></div>
                        </template>
                    </div>
                </template>
            </div>
            <div id="dock">
                <div id="keyboard" :class="{ 'hidden': finished }" @click.stop="onScreenKey($event)" @mousedown="keepFocusOffKeys($event)">
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
                {{-- A finished Game needs no keyboard, so what to do next takes its place without moving the board.
                     A player who finished from a focused key carries on from Share, which reads out the message it took focus from,
                     and hears what sharing did. --}}
                <template x-if="finished">
                    <div id="finished" aria-live="polite" x-init="$root.querySelector('#keyboard').contains(document.activeElement) && $el.querySelector('.share').focus()">
                        <button class="share" type="button" aria-describedby="message" @click="share()" x-text="shareLabel"></button>
                        @guest
                            <a class="save-result" href="{{ route('login', ['signup' => 1]) }}">Create an account to save this result</a>
                        @endguest
                        {{-- A timer is not announced as it ticks, so the panel's live region stays quiet until the new Game is ready. --}}
                        <template x-if="nextGameIn">
                            <div class="countdown" role="timer" aria-live="off">
                                <span class="countdown-label">Next game available in</span>
                                <span class="readout">
                                    <span class="unlit" aria-hidden="true" x-text="countdown.replace(/\d/g, '8')"></span>
                                    <span x-text="countdown"></span>
                                </span>
                            </div>
                        </template>
                        <template x-if="!nextGameIn">
                            <button class="play-next" type="button" @click="location.reload()">Play today's game</button>
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
        @if (app()->isLocal())
            @include('partials.keys-prototype')
        @endif
    </body>
</html>
