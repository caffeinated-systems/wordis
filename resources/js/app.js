import Alpine from 'alpinejs';
import game from './game';

Alpine.data('game', () => game);

window.Alpine = Alpine;
Alpine.start();
