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
        for (let tile of row) {
            tile.updateStatus(theWord);
        }

        row.filter(tile => tile.status === 'present')
            .filter((tile) =>
                row.some(
                    (t) => t.letter === tile.letter && t.status === 'correct'
                )
            )
            .forEach((tile) => (tile.status = 'absent'));
    }

    updateStatus(theWord) {

        if (!theWord.includes(this.letter)) {
            return this.status = 'absent';
        }
        if (this.letter === theWord[this.position]) {
            return this.status = 'correct';
        }

        return this.status = 'present';
    }
}
