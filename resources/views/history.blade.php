<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>History · Wordis</title>
        <link rel="icon" href="/favicon.ico" sizes="32x32">
        <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
        @vite(['resources/css/app.css'])
    </head>
    <body>
        <main class="history">
            <header>
                @include('partials.account')
                <h1>
                    <a href="/"><img id="wordis-logo" src='/assets/wordis-logo.svg' alt='Wordis' width="4460" height="995" /></a>
                </h1>
            </header>

            <section class="stats" aria-label="Your stats">
                <dl>
                    <div><dt>Played</dt><dd>{{ $stats['played'] }}</dd></div>
                    <div><dt>Won</dt><dd>{{ $stats['winRate'] }}%</dd></div>
                    <div class="streak"><dt>Streak</dt><dd>{{ $stats['currentStreak'] }}</dd></div>
                    <div><dt>Best streak</dt><dd>{{ $stats['bestStreak'] }}</dd></div>
                </dl>

                @php($mostGames = max(1, ...array_values($stats['distribution'])))
                <ol class="distribution" aria-label="Games by number of guesses">
                    @foreach ($stats['distribution'] as $guesses => $count)
                        <li @class(['lost' => $guesses === 'lost']) style="--share: {{ $count / $mostGames }}">
                            <span>{!! $guesses === 'lost' ? '<span aria-hidden="true">✕</span><span class="visually-hidden">Lost</span>' : $guesses !!}</span>
                            <span class="bar"></span>
                            <span>{{ $count }}</span>
                        </li>
                    @endforeach
                </ol>
            </section>

            <section class="calendar" aria-labelledby="month">
                <nav class="months">
                    @if ($previousMonth)
                        <a href="{{ route('history', ['month' => $previousMonth]) }}" aria-label="Previous month" rel="prev">‹</a>
                    @else
                        <span aria-hidden="true">‹</span>
                    @endif
                    <h2 id="month">{{ $month->format('F Y') }}</h2>
                    @if ($nextMonth)
                        <a href="{{ route('history', ['month' => $nextMonth]) }}" aria-label="Next month" rel="next">›</a>
                    @else
                        <span aria-hidden="true">›</span>
                    @endif
                </nav>

                <ol class="days">
                    @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
                        <li class="weekday" aria-hidden="true">{{ $weekday[0] }}</li>
                    @endforeach

                    @foreach ($cells as $index => $cell)
                        @if ($cell === null)
                            <li aria-hidden="true"></li>
                            @continue
                        @endif

                        @php($game = $cell['game'])
                        @php($label = \Illuminate\Support\Carbon::parse($cell['date'])->format('j F').': '.match ($game['outcome'] ?? null) {
                            'won' => 'won in '.count($game['rows']).' '.Str::plural('guess', count($game['rows'])),
                            'lost' => 'lost',
                            'unfinished' => $cell['date'] === $today ? 'in progress' : 'unfinished',
                            null => $cell['date'] > $today ? 'still to come' : 'not played',
                        })
                        <li @class([
                            'day',
                            $game['outcome'] ?? ($cell['date'] > $today ? 'future' : 'missed'),
                            'today' => $cell['date'] === $today,
                        ]) style="--order: {{ $index }}">
                            @if ($game === null)
                                <span class="day-number" aria-label="{{ $label }}">{{ $cell['day'] }}</span>
                            @else
                                {{-- Today's unfinished Game would spoil the answer, so it goes back to the board instead. --}}
                                @php($tag = $game['answer'] === null ? 'a' : 'button')
                                <{{ $tag }} @if ($tag === 'a') href="/" @else type="button" commandfor="day-{{ $cell['date'] }}" command="show-modal" @endif aria-label="{{ $label }}{{ $tag === 'a' ? '. Carry on playing' : '' }}">
                                    <span class="day-number" aria-hidden="true">{{ $cell['day'] }}</span>
                                    <span class="mosaic" aria-hidden="true">
                                        @for ($row = 0; $row < \App\Http\Controllers\GameController::GUESSES_ALLOWED; $row++)
                                            @for ($letter = 0; $letter < 5; $letter++)
                                                <i class="{{ $game['rows'][$row]['statuses'][$letter] ?? '' }}"></i>
                                            @endfor
                                        @endfor
                                    </span>
                                </{{ $tag }}>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>

            @foreach ($cells as $cell)
                @if ($cell['game']['answer'] ?? null)
                    <dialog id="day-{{ $cell['date'] }}" closedby="any" aria-labelledby="day-{{ $cell['date'] }}-title">
                        <h2 id="day-{{ $cell['date'] }}-title">{{ \Illuminate\Support\Carbon::parse($cell['date'])->format('l j F') }}</h2>
                        <div class="board">
                            @for ($row = 0; $row < \App\Http\Controllers\GameController::GUESSES_ALLOWED; $row++)
                                <div class="row">
                                    @for ($letter = 0; $letter < 5; $letter++)
                                        <div class="tile {{ $cell['game']['rows'][$row]['statuses'][$letter] ?? '' }}">{{ $cell['game']['rows'][$row]['word'][$letter] ?? '' }}</div>
                                    @endfor
                                </div>
                            @endfor
                        </div>
                        <p class="answer {{ $cell['game']['outcome'] }}">
                            @if ($cell['game']['outcome'] === 'won')
                                Found <strong>{{ $cell['game']['answer'] }}</strong> in {{ count($cell['game']['rows']) }}
                            @else
                                The answer was <strong>{{ $cell['game']['answer'] }}</strong>
                            @endif
                        </p>
                        <button type="button" class="close" commandfor="day-{{ $cell['date'] }}" command="close">Close</button>
                    </dialog>
                @endif
            @endforeach
        </main>
    </body>
</html>
