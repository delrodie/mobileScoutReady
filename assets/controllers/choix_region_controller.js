import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    select(event){
        const label = event.currentTarget
        const input = label.querySelector('input[type="radio"]')
        if (input) {
            input.checked = true
            this.element.requestSubmit()
        }
    }
}
