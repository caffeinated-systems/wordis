import Alpine from 'alpinejs';
import game from './game';

declare global {
    interface Window {
        Alpine: typeof Alpine;
    }
}

Alpine.data('game', game);

window.Alpine = Alpine;
Alpine.start();
