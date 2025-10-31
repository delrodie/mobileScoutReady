import { Controller } from "@hotwired/stimulus";

// Gère l'affichage du profil et la désactivation du lien en mode offline
export default class extends Controller {

    connect() {
        this.link = this.element.querySelector("#edit-link");

        // Vérifie l'état initial
        this.updateOnlineStatus();

        // Écoute les changements de connectivité
        window.addEventListener("online", () => this.updateOnlineStatus());
        window.addEventListener("offline", () => this.updateOnlineStatus());

        // Intercepte le clic sur le lien de modification
        if (this.link) {
            this.link.addEventListener("click", (event) => {
                if (!navigator.onLine) {
                    event.preventDefault();
                    this.showOfflineToast();
                }
            });
        }
    }

    updateOnlineStatus() {
        const isOnline = navigator.onLine;

        if (this.link) {
            this.link.classList.toggle("disabled", !isOnline);
            this.link.style.pointerEvents = isOnline ? "auto" : "none";
            this.link.style.opacity = isOnline ? "1" : "0.5";
        }
    }

    showOfflineToast() {
        // Vérifie s'il existe déjà un toast visible
        if (document.getElementById("offline-toast")) return;

        // Crée le toast Bootstrap
        const toast = document.createElement("div");
        toast.id = "offline-toast";
        toast.className = "toast align-items-center text-bg-warning border-0 position-fixed bottom-0 start-50 translate-middle-x mb-4 shadow";
        toast.style.zIndex = "2000";
        toast.style.minWidth = "280px";
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-wifi-off me-2"></i>
                    Impossible de modifier les informations sans connexion Internet.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fermer"></button>
            </div>
        `;

        document.body.appendChild(toast);

        // Initialise le toast Bootstrap
        const bootstrapToast = new bootstrap.Toast(toast, { delay: 3000 });
        bootstrapToast.show();

        // Supprime le toast après sa disparition
        toast.addEventListener("hidden.bs.toast", () => toast.remove());
    }
}
