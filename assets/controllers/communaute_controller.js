import { Controller } from '@hotwired/stimulus';
import LocalDbController from './local_db_controller.js';

const ITEMS_PER_PAGE = 10;

export default class extends Controller {
    // Ajout de la target 'pagination' (l'espion en bas de page)
    static targets = ['list', 'template', 'loader', 'empty', 'input', 'pagination', 'avatarLoader'];
    static values = {
        apiUrl: String
    }

    allScouts = [];      // La liste brute complète venant de l'API
    currentList = [];    // La liste en cours d'affichage (filtrée ou non)
    currentPage = 1;     // Page actuelle du scroll

    async connect() {
        // Initialisation de l'observer pour le scroll infini
        this.setupObserver();
        await this.loadCommunaute();
    }

    disconnect() {
        if (this.observer) this.observer.disconnect();
    }

    /**
     * Configuration de l'IntersectionObserver (L'espion de scroll)
     */
    setupObserver() {
        const options = {
            root: null, // viewport
            rootMargin: '0px',
            threshold: 0.1
        };


        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                // Si l'élément de pagination est visible et qu'on a des données
                if (entry.isIntersecting && this.currentList.length > 0) {
                    this.loadMore();
                }
            });
        }, options);
    }

    async loadCommunaute() {
        try {
            const profil = await LocalDbController.getAllFromStore('profil');
            const instance = await LocalDbController.getAllFromStore('profil_instance');

            if (!profil || profil.length === 0) {
                console.warn("Aucun profil local trouvé.");
                this.loaderTarget.classList.add('d-none');
                return;
            }

            console.log("👤 Profil local récupéré :", profil[0]);

            const response = await fetch(this.apiUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    slug: profil[0].slug,
                    code: profil[0].code,
                    instance: instance && instance.length > 0 ? instance[0].id : null,
                    parentId: instance && instance.length > 0 ? instance[0].parentId : null,
                })
            });

            if (!response.ok) throw new Error('Erreur API');

            const scouts = await response.json();

            // Stockage
            this.allScouts = scouts;
            this.currentList = scouts; // Au début, la liste courante = tout le monde

            this.loaderTarget.classList.add('d-none');

            if (!scouts || scouts.length === 0) {
                this.emptyTarget.classList.remove('d-none');
                return;
            }

            // Initialisation du premier affichage (Page 1)
            this.resetPaginationAndRender();

        } catch (error) {
            console.error("❌ Erreur chargement communauté :", error);
            if (this.hasLoaderTarget) this.loaderTarget.classList.add('d-none');
        }
    }

    /**
     * Recherche dynamique
     */
    search() {
        const query = this.normalize(this.inputTarget.value);

        if (query.length === 0) {
            // Champ vide : on remet la liste complète
            this.currentList = this.allScouts;
            this.emptyTarget.classList.add('d-none');
            this.resetPaginationAndRender();
            return;
        }

        // Filtrage
        this.currentList = this.allScouts.filter(scout => {
            const searchableText = this.normalize(
                `${scout.nom} ${scout.prenom} ${scout.fonction || ''} ${scout.instance || ''}`
            );
            return searchableText.includes(query);
        });

        // Gestion vide
        if (this.currentList.length === 0) {
            this.listTarget.innerHTML = '';
            this.emptyTarget.classList.remove('d-none');
            this.unobservePagination(); // On cache l'espion car rien à charger
        } else {
            this.emptyTarget.classList.add('d-none');
            // On réinitialise l'affichage avec les résultats filtrés
            this.resetPaginationAndRender();
        }
    }

    /**
     * Réinitialise le compteur de page et affiche le premier chunk
     */
    resetPaginationAndRender() {
        this.currentPage = 1;
        this.listTarget.innerHTML = ''; // On vide le DOM
        this.renderChunk(1);            // On affiche les 20 premiers
        this.observePagination();       // On réactive l'espion
    }

    /**
     * Appelé par l'Observer quand on arrive en bas
     */
    loadMore() {
        const maxPage = Math.ceil(this.currentList.length / ITEMS_PER_PAGE);

        if (this.currentPage < maxPage) {
            this.currentPage++;
            console.log(`📜 Chargement page ${this.currentPage}/${maxPage}`);
            this.renderChunk(this.currentPage);
        } else {
            // Plus rien à charger, on arrête d'observer
            this.unobservePagination();
        }
    }

    /**
     * Affiche une tranche de la liste courante
     */
    renderChunk(page) {
        const start = (page - 1) * ITEMS_PER_PAGE;
        const end = start + ITEMS_PER_PAGE;
        const chunk = this.currentList.slice(start, end);

        chunk.forEach(scout => {
            this.appendScoutNode(scout);
        });
    }

    /**
     * Crée et insère un élément DOM pour un scout
     */
    appendScoutNode(scout) {
        const clone = this.templateTarget.content.cloneNode(true);

        const nomComplet = `${scout.nom.toUpperCase()} ${scout.prenom.toLowerCase()}`;

        const elNom = clone.querySelector('.js-nom');
        if (elNom) elNom.textContent = nomComplet;

        const elFonction = clone.querySelector('.js-fonction');
        if (elFonction) elFonction.textContent = scout.fonction || 'Scout';

        const elInstance = clone.querySelector('.js-instance');
        if (elInstance) elInstance.textContent = scout.instance || '';

        const elValidation = clone.querySelector('.js-validation');
        if (elValidation) elValidation.textContent = scout.validation || '';


        // Avatar du membre
        const avatarContainer = clone.querySelector('[data-controller="image-loader"]');

        if (avatarContainer && scout.avatar) {
            const avatarPath = `${scout.avatar}`;

            avatarContainer.setAttribute('data-image-loader-src-value', avatarPath);

            // Gestion native si besoin
            avatarContainer.setAttribute('data-image-loader-native-src-value', `asset://${avatarPath}`);
        }

        //Lien profil membre
        const urlContainer = clone.querySelector('.url-membre')
        if (urlContainer && scout.url){
            urlContainer.setAttribute('href', scout.url);
        }

        this.listTarget.appendChild(clone);
    }

    normalize(str) {
        return str.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    }

    observePagination() {
        if (this.hasPaginationTarget) {
            this.observer.observe(this.paginationTarget);
            this.paginationTarget.classList.remove('d-none');
        }
    }

    unobservePagination() {
        if (this.hasPaginationTarget) {
            this.observer.unobserve(this.paginationTarget);
            this.paginationTarget.classList.add('d-none');
        }
    }
}
