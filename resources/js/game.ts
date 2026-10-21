import confetti from 'canvas-confetti';
import Tile, { TileStatus } from './Tile';

export type GameState = 'active' | 'complete';

type Heat = 'cold' | 'warm' | 'hot';

export function heat(statuses: TileStatus[]): Heat {
    const score = statuses.reduce((total, status) => total + (status === 'correct' ? 2 : status === 'present' ? 1 : 0), 0);

    return score <= 2 ? 'cold' : score <= 6 ? 'warm' : 'hot';
}

type GuessResult = {
    statuses: TileStatus[];
    answer?: string;
};

// The Game the server has recorded so far, so a reload resumes it.
export type SavedGame = {
    guesses: { word: string; statuses: TileStatus[] }[];
    answer?: string;
};

export default function game(saved: SavedGame = { guesses: [] }) {
    return {
        guessesAllowed: 5,
        wordLength: 5,
        answer: '',
        currentRowIndex: 0,
        state: 'active' as GameState,
        won: false,
        checking: false,
        pending: Promise.resolve(),
        errors: false,
        message: '',
        board: [] as Tile[][],
        letters: [
            "QWERTYUIOP".split(""),
            "ASDFGHJKL".split(""),
            ['Enter', ..."ZXCVBNM".split(""), 'Backspace'],
        ],
        // A wrong guess scores 2 per correct tile and 1 per present tile; the closer it is, the gentler the diss.
        disses: {
            cold: [
                'Are you even trying?',
                'Not even close',
                'Did you sneeze on the keyboard?',
                'That was painful to watch',
                'Bold choice. Wrong, but bold',
            ],
            warm: [
                'Nope',
                'Wrong',
                'Meh',
                'Getting somewhere, slowly',
                'Lukewarm at best',
            ],
            hot: [
                'So close, and still wrong',
                'Oh, nearly',
                'Ooh, hot',
                'You can almost taste it',
                "Don't blow it now",
            ],
        } satisfies Record<Heat, string[]>,
        // The win message for each guess number, first guess first.
        wins: [
            'Lucky',
            'Show-off',
            'Not bad, I suppose',
            'Took your time',
            'Finally',
        ],
        get currentRow(): Tile[] {
            return this.board[this.currentRowIndex];
        },
        get currentGuess(): string {
            return this.currentRow.map((tile) => tile.letter).join("");
        },
        get remainingGuesses(): number {
            return this.guessesAllowed - this.currentRowIndex -1;
        },
        init(): void {
            this.board = Array.from({ length: this.guessesAllowed }, () => {
                return Array.from({ length: this.wordLength }, (item, index) => new Tile(index));
            });
            for (const { word, statuses } of saved.guesses) {
                [...word].forEach((letter) => this.fillTile(letter));
                this.applyResult({ statuses, answer: saved.answer }, false);
            }
            // A Game in progress resumes quietly; a finished one shows its message again.
            if (this.state === 'active') {
                this.message = '';
            }
        },
        matchingTileForKey(key: string): Tile | undefined {
            const tiles = this.board.flat().filter((tile) => tile.letter === key.toLowerCase());

            return tiles.find((tile) => tile.status === 'correct')
                ?? tiles.find((tile) => tile.status === 'present')
                ?? tiles.find((tile) => tile.status === 'absent');
        },
        onPhysicalKey(event: KeyboardEvent): void {
            // Enter on a focused on-screen key, link or button has already activated it, so it isn't a guess.
            if (event.key === 'Enter' && (event.target as Element).closest('a, button')) {
                return;
            }
            this.onKeyPress(event.key);
        },
        keepFocusOffKeys(event: MouseEvent): void {
            // A tap must not leave a key or footer link focused, or a physical Enter would press it again instead of guessing.
            // Tab still focuses them for players who use them from the keyboard.
            event.preventDefault();
            (document.activeElement as HTMLElement | null)?.blur();
        },
        onScreenKey(event: MouseEvent): void {
            const button = (event.target as Element).closest('button');
            if (button) {
                this.onKeyPress(button.dataset.key!);
            }
        },
        onKeyPress(key: string): void {
            if (this.checking || this.state === 'complete') {
                return;
            }
            this.message = '';
            this.errors = false;
            if(/^[a-z]$/i.test(key)) {
                this.fillTile(key);
            } else if (key === 'Backspace') {
                this.emptyTile();
            } else if (key === 'Enter') {
                this.checkWord();
            }
        },
        fillTile(key: string): void {
            for(let tile of this.currentRow) {
                if (!tile.letter ){
                    tile.fill(key);
                    break;
                }
            }
        },
        emptyTile(): void {
            for(let tile of [...this.currentRow].reverse()) {
                if (tile.letter){
                    tile.empty();
                    break;
                }
            }
        },

        checkWord(): void {
            if (this.currentGuess.length < this.wordLength) {
                return;
            }
            this.pending = this.submitGuess();
        },
        async submitGuess(): Promise<void> {
            this.checking = true;
            try {
                const response = await fetch('/guesses', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ guess: this.currentGuess }),
                });
                if (!response.ok) {
                    this.errors = true;
                    this.message = response.status === 422 ? 'Not in word list'
                        : response.status === 409 ? 'This game is over'
                        : response.status === 419 ? 'Your session expired, reload the page'
                        : response.status === 429 ? 'Slow down a bit'
                        : 'Something went wrong, try again';
                    return;
                }
                this.applyResult(await response.json());
            } catch {
                this.errors = true;
                this.message = 'Something went wrong, try again';
            } finally {
                this.checking = false;
            }
        },
        applyResult({ statuses, answer }: GuessResult, celebrate = true): void {
            this.currentRow.forEach((tile, index) => tile.status = statuses[index]);

            if (statuses.every((status) => status === 'correct')) {
                this.state = 'complete';
                this.won = true;
                this.message = this.wins[this.currentRowIndex];
                if (celebrate) {
                    confetti();
                }
            } else if (this.remainingGuesses === 0) {
                this.state = 'complete';
                this.answer = answer ?? '';
                this.message = `Ha ha, sucka! It was ${this.answer.toUpperCase()}`;
            } else {
                this.currentRowIndex++;
                const disses = this.disses[heat(statuses)];
                this.message = disses[Math.floor(Math.random() * disses.length)];
            }
        },
    };
}
