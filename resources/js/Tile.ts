export type TileStatus = '' | 'correct' | 'present' | 'absent';

export default class Tile {
    letter = '';
    status: TileStatus = '';
    position: number;

    constructor(position: number) {
        this.position = position;
    }

    fill(key: string): void {
        this.letter = key.toLowerCase();
    }

    empty(): void {
        this.letter = '';
    }

    static updateStatusForRow(row: Tile[], answer: string): void {
        let remainingLetters = answer.split('');

        for (let tile of row) {
            tile.status = tile.letter === answer[tile.position] ? 'correct' : '';

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
