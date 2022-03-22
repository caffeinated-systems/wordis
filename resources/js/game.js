import Tile from './Tile';
export default {
        guessesAllowed: 4,
        theWord: 'dogs',
        currentRowIndex: 0,
        state: 'active',
        message: '',
        get currentRow() {
            return this.board[this.currentRowIndex];
        },
        get currentGuess() {
            return this.currentRow.map(tile => tile.letter).join('');
        },
        get remainingGuesses() {
            return this.guessesAllowed - this.currentRowIndex +1
        },
        init() {
            this.board = Array.from({ length: this.guessesAllowed }, () => {
                return Array.from({ length: this.theWord.length }, (item, index) => new Tile(index));
            });
        },
        onKeyPress(key){
            this.message = '';
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
                if (tile.letter === ''){
                    tile.letter = key;
                    break;
                }
            }
        },
        emptyTile() {
            for(let tile of [...this.currentRow].reverse()) {
                if (tile.letter !== ''){
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

            // deal with invalid words

            Tile.updateStatusForRow(this.currentRow, this.theWord);
            if(this.currentGuess === this.theWord) {
                this.state = 'complete';
                return this.message = 'You win!';
            }
            if( this.remaininGuesses === 0) {
                this.state = 'complete';
                return this.message = 'You lose!';
            }
            this.currentRowIndex++;
            return this.message = 'Nope';
        },

        refreshTileStatus(){
            for(let tile of this.currentRow){
                tile.updateStatus(this.currentGuess, this.theWord);
            };
        }
    }

