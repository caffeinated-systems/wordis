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
}
