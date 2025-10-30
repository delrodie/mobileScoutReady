import { Controller } from "@hotwired/stimulus"

/**
 * Contrôleur Stimulus pour surveiller l'état du réseau (online/offline)
 * et afficher une bannière d'information en haut de l'écran.
 */
export default class extends Controller {
    static values = {
        autohide: { type: Boolean, default: true },
        duration: { type: Number, default: 3000 }
    }

    // Clé pour stocker l'état hors ligne dans sessionStorage
    static OFFLINE_KEY = "hasBeenOffline"

    connect() {
        console.log("🧩 NetworkStatusController connecté")

        // Crée la bannière une seule fois
        this.banner = document.createElement("div")
        this.banner.id = "network-status-banner"
        Object.assign(this.banner.style, {
            position: "fixed",
            top: "0",
            left: "0",
            width: "100%",
            textAlign: "center",
            fontWeight: "500",
            fontSize: "12px",
            padding: "1px",
            zIndex: "9999",
            transition: "all 0.3s ease-in-out",
            display: "none",
            fontFamily: "system-ui, sans-serif",
            boxShadow: "0 2px 2px rgba(0,0,0,0.1)"
        })
        document.body.appendChild(this.banner)

        // État initial : Appel de updateBanner UNIQUEMENT si on est OFFLINE
        // ou si on revient d'un état OFFLINE
        const isOnline = navigator.onLine
        const hasBeenOffline = sessionStorage.getItem(this.constructor.OFFLINE_KEY) === "true"

        if (!isOnline || hasBeenOffline) {
            this.updateBanner(isOnline)
        }

        // Écoute les changements réseau
        this.onlineHandler = () => this.updateBanner(true)
        this.offlineHandler = () => this.updateBanner(false)

        window.addEventListener("online", this.onlineHandler)
        window.addEventListener("offline", this.offlineHandler)
    }

    disconnect() {
        // Nettoyage à la déconnexion du contrôleur
        window.removeEventListener("online", this.onlineHandler)
        window.removeEventListener("offline", this.offlineHandler)
        if (this.banner) this.banner.remove()
    }

    updateBanner(isOnline) {
        if (isOnline) {
            // Logique pour l'état en ligne
            const hasBeenOffline = sessionStorage.getItem(this.constructor.OFFLINE_KEY) === "true"

            if (hasBeenOffline) {
                // S'affiche UNIQUEMENT si on revient d'un état offline
                this.banner.style.display = "block"
                this.banner.textContent = "🟢 Connexion rétablie"
                this.banner.style.backgroundColor = "#16a34a"
                this.banner.style.color = "white"
                this.banner.style.transform = "translateY(0)"
                this.banner.style.opacity = "1"

                if (this.autohideValue) {
                    setTimeout(() => {
                        this.banner.style.transform = "translateY(-100%)"
                        this.banner.style.opacity = "0"
                        // Optionnel : Retirer la bannière complètement après l'animation
                        setTimeout(() => this.banner.style.display = "none", 300)
                    }, this.durationValue)
                }

                // On réinitialise l'indicateur dans la session
                sessionStorage.removeItem(this.constructor.OFFLINE_KEY)
            } else {
                // Ne rien faire si on est déjà en ligne et qu'on ne revient pas d'un état hors ligne
                // Masquer la bannière au cas où elle était visible
                this.banner.style.display = "none"
            }

        } else {
            // Logique pour l'état hors ligne (s'affiche toujours)
            this.banner.style.display = "block"
            this.banner.textContent = "🔴 Vous êtes hors ligne"
            this.banner.style.backgroundColor = "#dc2626"
            this.banner.style.color = "white"
            this.banner.style.transform = "translateY(0)"
            this.banner.style.opacity = "1"

            // On enregistre que l'utilisateur est passé hors ligne dans cette session
            sessionStorage.setItem(this.constructor.OFFLINE_KEY, "true")
        }
    }
}
