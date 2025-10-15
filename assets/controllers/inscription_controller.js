import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["button"]

    submit(event) {
        this.buttonTarget.disabled = true
        this.buttonTarget.textContent = "Chargement..."
    }
}
