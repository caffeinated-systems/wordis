<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Wordis</title>
        <link rel="stylesheet" href="/css/app.css">
        <script src="//unpkg.com/alpinejs" defer></script>
        <script src="/js/app.js"></script>
    </head>
    <body>
        <h1>Wordis</h1>
        <main  x-data="game" @keyup.window="onKeyPress($event.key)">
            <div id="game">
                <template x-for="row in board">
                    <div class="row">
                        <template x-for="tile in row">
                            <div class="tile" :class="tile.status" x-text="tile.letter"></div>
                        </template>
                    </div>
                </template>
            </div>
            <output class="message" x-text="message"></output>
        </main>
    </body>
</html>
