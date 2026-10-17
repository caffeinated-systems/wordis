import Tile from './Tile';
import { five_letter_words }  from './five_letter_words';
export default {
        guessesAllowed: 5,
        theWord: 'tiger',
        currentRowIndex: 0,
        state: 'active',
        errors: false,
        message: '',
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
        get currentRow() {
            return this.board[this.currentRowIndex];
        },
        get currentGuess() {
            return this.currentRow.map((tile) => tile.letter).join("");
        },
        get remainingGuesses() {
            return this.guessesAllowed - this.currentRowIndex -1;
        },
        init() {
            this.board = Array.from({ length: this.guessesAllowed }, () => {
                return Array.from({ length: this.theWord.length }, (item, index) => new Tile(index));
            });
        },
        matchingTileForKey(key) {
            return this.board
                .flat()
                .filter((tile) => tile.status)
                .sort((t1, t2) => (t2.status === "correct") ? 1 : -1)
                .find((tile) => tile.letter === key.toLowerCase());
        },
        onKeyPress(key){
            this.message = '';
            this.errors = false;
            if(/^[A-z]$/.test(key)) {
                this.fillTile(key);
            } else if (key === 'Backspace') {
                this.emptyTile();
            } else if (key === 'Enter') {
                this.checkWord();
            }
        },
        fillTile(key) {
            for(let tile of this.currentRow) {
                if (!tile.letter ){
                    tile.fill(key);
                    break;
                }
            }
        },
        emptyTile() {
            for(let tile of [...this.currentRow].reverse()) {
                if (tile.letter){
                    tile.empty();
                    break;
                }
            }
        },

        checkWord()
        {
            if(this.currentGuess.length < this.theWord.length) {
                return;
            }
            if (!five_letter_words.includes(this.currentGuess.toUpperCase())) {
                this.errors = true;
                this.message = "Cheater, thats not a word...";
                return;
            }
            // deal with invalid words
            Tile.updateStatusForRow(this.currentRow, this.theWord);

            if(this.currentGuess === this.theWord) {
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
    }

