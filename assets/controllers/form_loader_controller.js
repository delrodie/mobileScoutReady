import { Controller } from '@hotwired/stimulus'

export default class extends Controller{
    static targets = ['button', 'spinner', 'text', 'loading']

    connect() {
        //super.connect();
        if(this.loadingTarget) this.loadingTarget.classList.add("hidden")
        if(this.spinnerTarget) this.spinnerTarget.classList.add('hidden')
    }

    disable(event){
        this.buttonTarget.disabled = true
        this.buttonTarget.classList.add("opacity-75", "cursor-not-allowed")

        if (this.textTarget) this.textTarget.classList.add("hidden")

        // Affiche le spinner
        if (this.spinnerTarget) this.spinnerTarget.classList.remove("hidden")
        if (this.loadingTarget) this.loadingTarget.classList.remove("hidden")

        // Animation
        this.buttonTarget.classList.add("transition-all", "duration-300")
    }

    enable(event){
        // Si la requete a échoué
        if (!event.detail.success) {
            // Reactive le bouton
            this.buttonTarget.disabled = false
            this.buttonTarget.classList.remove("opacity-75", "cursor-not-allowed")

            // Réinitialise le contenu
            if (this.spinnerTarget) this.spinnerTarget.classList.add('hidden')
            if (this.loadingTarget) this.loadingTarget.classList.add('hidden')
            if (this.textTarget) this.textTarget.classList.remove("hidden")

            console.log('Reactivation du bouton après echec de la requête')
        }
    }
}
