import confetti from 'canvas-confetti';
import Tile, { TileStatus } from './Tile';

export type GameState = 'active' | 'complete';

type GuessResult = {
    statuses: TileStatus[];
    answer?: string;
};

export default function game() {
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
        disses: [
            'Nope',
            'Wrong',
            'Not even close',
            'Stupid failure',
            'You are kidding, right?',
            'You are a loser',
            'Are you even trying?',
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
        },
        matchingTileForKey(key: string): Tile | undefined {
            const tiles = this.board.flat().filter((tile) => tile.letter === key.toLowerCase());

            return tiles.find((tile) => tile.status === 'correct')
                ?? tiles.find((tile) => tile.status === 'present')
                ?? tiles.find((tile) => tile.status === 'absent');
        },
        onPhysicalKey(event: KeyboardEvent): void {
            // Enter on a focused on-screen key has already pressed that key.
            if (event.key === 'Enter' && (event.target as Element).closest('.key')) {
                return;
            }
            this.onKeyPress(event.key);
        },
        keepFocusOffKeys(event: MouseEvent): void {
            // A tap must not leave a key focused, or a physical Enter would press it again.
            // Tab still focuses keys for players who use them from the keyboard.
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
            if (this.checking) {
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
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({ guess: this.currentGuess, attempt: this.currentRowIndex + 1 }),
                });
                if (!response.ok) {
                    this.errors = true;
                    this.message = response.status === 422 ? 'Not in word list'
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
        applyResult({ statuses, answer }: GuessResult): void {
            this.currentRow.forEach((tile, index) => tile.status = statuses[index]);

            if (statuses.every((status) => status === 'correct')) {
                this.state = 'complete';
                this.won = true;
                this.message = 'Alright, you win';
                confetti();
            } else if (this.remainingGuesses === 0) {
                this.state = 'complete';
                this.answer = answer ?? '';
                this.message = `Ha ha, sucka! It was ${this.answer.toUpperCase()}`;
            } else {
                this.currentRowIndex++;
                this.message = this.disses[Math.floor(Math.random() * this.disses.length)];
            }
        },
    };
}
