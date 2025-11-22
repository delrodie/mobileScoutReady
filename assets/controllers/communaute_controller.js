import { Controller } from '@hotwired/stimulus';
import LocalDbController from './local_db_controller.js';

export default class extends Controller {
    static targets = ['list', 'template', 'loader', 'empty', 'input'];
    static values = {
        apiUrl: String
    }

    allScouts = [];

    async connect() {
        await this.loadCommunaute();
    }

    async loadCommunaute() {
        try {
            // 1. Récupération du profil local via notre Helper
            const profil = await LocalDbController.getAllFromStore('profil');
            const instance = await LocalDbController.getAllFromStore('profil_instance');

            if (!profil || profil.length === 0) {
                console.warn("Aucun profil local trouvé.");
                this.loaderTarget.classList.add('d-none');
                return;
            }

            console.log("👤 Profil local récupéré :", profil[0]);

            // 2. Appel API avec les infos du profil (ex: region ou instance)
            // On envoie l'ID ou le slug pour que le serveur détermine la région
            const response = await fetch(this.apiUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    slug: profil[0].slug,
                    code: profil[0].code,
                    instance: instance[0].id,
                    parentId: instance[0].parentId,
                })
            });

            if (!response.ok) throw new Error('Erreur API');

            const scouts = await response.json();

            // Sauvegarde de la liste complète
            this.allScouts = scouts;

            // 3. Affichage
            this.loaderTarget.classList.add('d-none');

            if (!scouts || scouts.length === 0) {
                this.emptyTarget.classList.remove('d-none');
                return;
            }

            this.renderList(scouts);

        } catch (error) {
            console.error("Erreur chargement communauté :", error);
            this.loaderTarget.classList.add('d-none');
            // Optionnel : Afficher un message d'erreur
        }
    }

    /**
     * 🔍 Méthode de recherche dynamique
     * Appelée à chaque frappe dans l'input
     */
    search() {
        const query = this.normalize(this.inputTarget.value);

        // Si champ vide, on réaffiche tout
        if (query.length === 0) {
            this.emptyTarget.classList.add('d-none');
            this.renderList(this.allScouts);
            return;
        }

        // Filtrage
        const filtered = this.allScouts.filter(scout => {
            // On construit une chaîne unique avec toutes les infos cherchables
            const searchableText = this.normalize(
                `${scout.nom} ${scout.prenom} ${scout.fonction || ''} ${scout.instance || ''}`
            );
            return searchableText.includes(query);
        });

        // Gestion état vide ou affichage
        if (filtered.length === 0) {
            this.listTarget.innerHTML = '';
            this.emptyTarget.classList.remove('d-none');
            // Optionnel : changer le texte de emptyTarget pour dire "Aucun résultat pour..."
        } else {
            this.emptyTarget.classList.add('d-none');
            this.renderList(filtered);
        }
    }

    /**
     * Utilitaire pour nettoyer le texte (minuscules, sans accents)
     * Ex: "Hélène" -> "helene"
     */
    normalize(str) {
        return str.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    }

    renderList(scouts) {
        this.listTarget.innerHTML = '';

        scouts.forEach(scout => {
            const clone = this.templateTarget.content.cloneNode(true);

            const nomComplet = `${scout.nom.toUpperCase()} ${scout.prenom.toLowerCase()}`;

            // ✅ Sécurisation : On vérifie si l'élément existe avant de modifier son texte
            // Cela empêche l'erreur "Cannot set properties of null"

            const elNom = clone.querySelector('.js-nom');
            if (elNom) elNom.textContent = nomComplet;
            console.log(` Le nom : ${elNom}`);

            const elFonction = clone.querySelector('.js-fonction');
            if (elFonction) elFonction.textContent = scout.fonction || 'Scout';

            const elInstance = clone.querySelector('.js-instance');
            if (elInstance) elInstance.textContent = scout.instance || '';

            // 👇 C'est ici que ça plantait car .js-validation n'est pas dans le HTML
            const elValidation = clone.querySelector('.js-validation');
            if (elValidation) elValidation.textContent = scout.validation || '';

            // Gestion Avatar
            const img = clone.querySelector('img');
            if (img && scout.avatar) {
                img.src = scout.avatar;
            }

            this.listTarget.appendChild(clone);
        });
    }
}
