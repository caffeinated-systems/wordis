<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Wordis</title>
        <link rel="stylesheet" href="{{ mix('/css/app.css') }}">
        <script src="//unpkg.com/alpinejs" defer></script>
        <script src="{{ mix('/js/app.js') }}"></script>
    </head>
    <body>
        <main  x-data="game" @keyup.window="onKeyPress($event.key)" >
            <header>
                <h1 aria-label="Wordis">
                    <img id="wordis-logo" src='/assets/wordis_logo.png' alt='Wordis' />
                </h1>
                <output class="message" x-text="message"></output>
            </header>
            <div id="game">
                <template x-for="(row, index) in board">
                    <div class="row" :class="{ 'current': currentRowIndex === index, 'invalid': currentRowIndex === index && errors}">
                        <template x-for="tile in row">
                            <div class="tile" :class="tile.status" x-text="tile.letter"></div>
                        </template>
                    </div>
                </template>
            </div>
            <div id="keyboard"  @click.stop="$event.target.closest('button') && onKeyPress($event.target.closest('button').dataset.key)">
                <template x-for="row in letters">
                    <div class="row">
                        <template x-for="key in row">
                            <button
                                class="key"
                                :class="matchingTileForKey(key)?.status"
                                type="button"
                                :data-key="key"
                                x-text="key">
                            </button>
                        </template>
                    </div>
            </div>
        </main>
    </body>
</html>
