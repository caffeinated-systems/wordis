import confetti from 'canvas-confetti';
import Tile from './Tile';
import { dictionary } from './dictionary';

export type GameState = 'active' | 'complete';

export default function game(answer: string) {
    return {
        guessesAllowed: 5,
        answer,
        currentRowIndex: 0,
        state: 'active' as GameState,
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
                return Array.from({ length: this.answer.length }, (item, index) => new Tile(index));
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
            if (event.key === 'Enter' && (event.target as Element).closest('button')) {
                return;
            }
            this.onKeyPress(event.key);
        },
        onScreenKey(event: MouseEvent): void {
            const button = (event.target as Element).closest('button');
            if (!button) {
                return;
            }
            // Drop focus after a tap so a physical Enter submits instead of pressing this key again.
            // Keyboard activation (detail 0) keeps focus for players moving through the keys with Tab.
            if (event.detail > 0) {
                button.blur();
            }
            this.onKeyPress(button.dataset.key!);
        },
        onKeyPress(key: string): void {
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

        checkWord(): void
        {
            if(this.currentGuess.length < this.answer.length) {
                return;
            }
            if (!dictionary.has(this.currentGuess.toUpperCase())) {
                this.errors = true;
                this.message = "Cheater, thats not a word...";
                return;
            }
            Tile.updateStatusForRow(this.currentRow, this.answer);

            if(this.currentGuess === this.answer) {
                this.state = 'complete';
                this.message = 'Alright, you win';
                confetti();
            } else if (this.remainingGuesses === 0) {
                this.state = 'complete';
                this.message = 'Ha ha, sucka!';
            } else {
                this.currentRowIndex++;
                this.message = this.disses[Math.floor(Math.random() * this.disses.length)];
            }
        },
    };
}
