import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        document.addEventListener('turbo:before-fetch-request', this.beforeFetch);
        document.addEventListener('turbo:before-render', this.beforeRender);
        document.addEventListener('turbo:render', this.onRender);

        document.body.classList.remove('fade-out');
        document.body.classList.add('fade-in');
        console.log('Controleur de transition de page activé.');
    }

    disconnect() {
        document.removeEventListener('turbo:before-fetch-request', this.beforeFetch);
        document.removeEventListener('turbo:before-render', this.beforeRender);
        document.removeEventListener('turbo-render', this.onRender);
    }

    beforeFetch = () => {
        document.body.classList.add('fade-out');
        document.body.classList.remove('fade-in');
    }

    beforeRender = (event) => {
        const newBody = event.detail.newBody;

        newBody.classList.add('fade-out');
        newBody.classList.remove('fade-in');

        document.body.classList.remove('fade-out');
    }

    onRender = () => {
        setTimeout(() => {
            document.body.classList.remove('fade-out');
            document.body.classList.add('fade-in');
        }, 10);
    }
}
