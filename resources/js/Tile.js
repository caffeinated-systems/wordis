export default class Tile {
    letter = '';
    status = '';

    constructor($postion) {
        this.position = $postion;
    }

    fill(key){
        this.letter = key.toLowerCase();
    }

    empty() {
        this.letter = '';
    }

    static updateStatusForRow(row, theWord){
        let remainingLetters = theWord.split('');

        for (let tile of row) {
            tile.status = tile.letter === theWord[tile.position] ? 'correct' : '';

            if (tile.status === 'correct') {
                remainingLetters.splice(remainingLetters.indexOf(tile.letter), 1);
            }
        }

        for (let tile of row.filter((tile) => tile.status !== 'correct')) {
            let index = remainingLetters.indexOf(tile.letter);

            if (index === -1) {
                tile.status = 'absent';
            } else {
                tile.status = 'present';
                remainingLetters.splice(index, 1);
            }
        }
    }
}
