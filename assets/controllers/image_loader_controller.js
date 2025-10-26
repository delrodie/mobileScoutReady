// controllers/image_loader_controller.js
import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static values = {
        src: String,             // URL web de l’image
        nativeSrc: String,       // URL ou chemin natif (ex: asset://image.jpg)
        preload: Boolean         // true pour précharger
    }

    connect() {
        if (this.preloadValue) {
            this.preloadImage(this.srcValue)
        }

        this.loadImage()
    }

    preloadImage(url) {
        const img = new Image()
        img.src = url
    }

    loadImage() {
        const img = this.element.querySelector("img")

        if (!img) return

        const isNative = this.isHotwireNative()

        if (isNative && this.hasNativeSrcValue) {
            img.src = this.nativeSrcValue
        } else {
            img.src = this.srcValue
        }
    }

    isHotwireNative() {
        return window.webkit?.messageHandlers?.turboNative !== undefined ||
            window.TurboNativeBridge !== undefined
    }
}
