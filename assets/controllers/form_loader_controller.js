import { Controller } from '@hotwired/stimulus'

export default class extends Controller{
    static targets = ['button', 'spinner', 'text', 'loading']

    connect() {
        //super.connect();
        if(this.loadingTarget) this.loadingTarget.classList.add("d-none")
        if(this.spinnerTarget) this.spinnerTarget.classList.add('d-none')
    }

    disable(event) {
        this.buttonTarget.disabled = true
        this.buttonTarget.classList.add("opacity-75", "cursor-not-allowed")

        // Change le texte du bouton
        if (this.textTarget) this.textTarget.classList.add("d-none")

        // Affiche le spinner
        if (this.spinnerTarget) this.spinnerTarget.classList.remove("d-none")
        if (this.loadingTarget) this.loadingTarget.classList.remove("d-none")

        this.buttonTarget.classList.add("transition-all")
    }

    enable(event) {
        if (!event.detail.success) {
            this.buttonTarget.disabled = false
            this.buttonTarget.classList.remove("opacity-75", "cursor-not-allowed")

            // Réinitialise le texte
            if (this.textTarget) {
                this.textTarget.textContent = "Soumettre"
                this.textTarget.classList.remove("d-none")
            }

            if (this.spinnerTarget) this.spinnerTarget.classList.add("d-none")
            if (this.loadingTarget) this.loadingTarget.classList.add("d-none")

            console.log('Reactivation du bouton après échec de la requête')
        }
    }
}
