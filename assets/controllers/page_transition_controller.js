import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        document.addEventListener('turbo:before-visit', this.beforeVisit);
        document.addEventListener('turbo:render', this.onRender);
        console.log('Controleur de transition de page activé.');
        document.body.classList.remove('turbo-transition');
    }

    disconnect() {
        document.removeEventListener('turbo:before-visit', this.beforeVisit);
        document.removeEventListener('turbo:render', this.onRender);
    }

    beforeVisit = () => {
        document.body.classList.add('turbo-transition');
    }

    onRender = () => {
        document.body.classList.remove('turbo-transition');
    }
}
