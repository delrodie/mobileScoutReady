// home_controller.js
import { Controller } from "@hotwired/stimulus";
import LocalDbController from "./local_db_controller.js";

export default class extends Controller {
    // Ajout de "toggleButton" aux targets
    static targets = ["champsContainer", "template", "toggleButton"];

    connect() {
        console.log("🏠 Home Controller connecté. Chargement des champs...");

        // Initialisation des variables d'état
        this.allChamps = []; // Pour stocker la liste complète
        this.limit = 6;      // Limite d'affichage
        this.isExpanded = false; // État actuel (replié par défaut)

        this.loadChampsFromLocalDb();
    }

    async loadChampsFromLocalDb() {
        try {
            const champs = await LocalDbController.getAllFromStore('champs_activite');

            if (champs && champs.length > 0) {
                // On stocke les données sans les afficher tout de suite
                this.allChamps = champs.filter(c => typeof c === 'object');

                // On lance l'affichage initial
                this.updateDisplay();
            } else {
                console.log("Aucun champ trouvé en local.");
                this.champsContainerTarget.innerHTML = '<p class="text-center text-muted">Aucun champ disponible.</p>';
                this.toggleButtonTarget.style.display = 'none'; // Cacher le bouton si vide
            }
        } catch (error) {
            console.error("Erreur lors du chargement des champs :", error);
        }
    }

    /**
     * Action appelée au clic sur "Voir plus" / "Réduire"
     */
    toggleVisibility(event) {
        event.preventDefault(); // Empêche le saut de page du lien #
        this.isExpanded = !this.isExpanded; // Inverse l'état
        this.updateDisplay(); // Met à jour l'affichage
    }

    /**
     * Gère quel sous-ensemble de données afficher et le texte du bouton
     */
    updateDisplay() {
        // 1. Déterminer quels champs afficher
        let champsToRender;

        if (this.isExpanded) {
            // Si déplié, on prend tout
            champsToRender = this.allChamps;
        } else {
            // Si replié, on prend les 'limit' premiers (ex: 0 à 6)
            champsToRender = this.allChamps.slice(0, this.limit);
        }

        // 2. Rendre les cartes
        this.renderChamps(champsToRender);

        // 3. Mettre à jour le bouton (Texte et Visibilité)
        if (this.hasToggleButtonTarget) {
            // Si on a moins de 6 éléments au total, pas besoin de bouton
            if (this.allChamps.length <= this.limit) {
                this.toggleButtonTarget.style.display = 'none';
            } else {
                this.toggleButtonTarget.style.display = 'inline-block';
                this.toggleButtonTarget.innerText = this.isExpanded ? "Réduire..." : "Voir plus";
            }
        }
    }

    renderChamps(champs) {
        this.champsContainerTarget.innerHTML = '';

        champs.forEach(champ => {
            // Note: Le filtrage "typeof champ !== 'object'" est déjà fait dans loadChampsFromLocalDb
            // mais on peut le laisser ici par sécurité si vous préférez.

            let imageSrc = champ.champActiviteBlob || champ.media;
            // let imageSrc =  champ.media;

            const cardHtml = `
                <div class="col">
                    <div class="card h-100 border-0">
                        <a href="${champ.urlDetail}" class="text-decoration-none text-secondary">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center text-center py-0">
                                <div class="mb-2">
                                    <img src="${imageSrc}"
                                         alt="${champ.titre}"
                                         class="img-fluid rounded-pill p-1 bg-main-30"
                                         style="max-height: 80px; object-fit: cover;"
                                         >
                                         <!--onerror="this.src='/img/fallback.png'"-->
                                </div>
                                <h5 class="fsize-13 py-1">${champ.titre}</h5>
                            </div>
                        </a>
                    </div>
                </div>
            `;
            this.champsContainerTarget.insertAdjacentHTML('beforeend', cardHtml);
        });
    }
}
