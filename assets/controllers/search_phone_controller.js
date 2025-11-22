import { Controller } from '@hotwired/stimulus';
import LoadDbController from './local_db_controller.js';

const DB_NAME = 'db_scoutready';
const DB_VERSION = 1.2; // 🔹 Incrémenté car on ajoute un nouveau champ (qrCodeLocal)

export default class extends Controller {
    static targets = ['form', 'phone'];

    async submit(event) {
        event.preventDefault();

        const form = this.formTarget;
        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            //console.log(` response: ${response}`);

            if (!response.ok) throw new Error("Erreur serveur");

            const data = await response.json();

            if (data.status === 'nouveau'){
                Turbo.visit('/inscription');
                return;
            }

            console.log("✅ Données reçues du backend:", data);

            console.log("Donnees unique : ", data.profil.isParent)

            if (data.profil.isParent === true){
                console.log("Profile parent")
                Turbo.visit('/intro/choix/profil');
                return;
            }

            await LoadDbController.saveToIndexedDB(data);
            // await this.saveToIndexedDB(data);

            // Redirection vers l’accueil après succès
            Turbo.visit('/accueil');

        } catch (error) {
            console.error("❌ Erreur lors de la soumission :", error);
            alert("Une erreur est survenue. Vérifiez votre connexion.");
        }
    }

    async saveToIndexedDB(data) {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // 🔹 Création / mise à jour des stores si nécessaires
                if (!db.objectStoreNames.contains('profil')) {
                    db.createObjectStore('profil', { keyPath: 'slug' });
                }
                if (!db.objectStoreNames.contains('profil_fonction')) {
                    db.createObjectStore('profil_fonction', { keyPath: 'id' });
                }
                if (!db.objectStoreNames.contains('profil_instance')) {
                    db.createObjectStore('profil_instance', { keyPath: 'id' });
                }
            };

            request.onsuccess = async (event) => {
                const db = event.target.result;
                const tx = db.transaction(['profil', 'profil_fonction', 'profil_instance'], 'readwrite');

                const profilStore = tx.objectStore('profil');
                const fonctionStore = tx.objectStore('profil_fonction');
                const instanceStore = tx.objectStore('profil_instance');

                // Nettoyage avant réinsertion
                profilStore.clear();
                fonctionStore.clear();
                instanceStore.clear();

                // Insertion des données
                profilStore.put(data.profil);
                fonctionStore.put(data.profil_fonction);
                instanceStore.put(data.profil_instance);

                tx.oncomplete = async () => {
                    console.log("💾 Données principales sauvegardées avec succès dans IndexedDB");

                    try {
                        // ⚡ Téléchargement et stockage du QR code APRÈS la transaction
                        await LoadDbController.fetchAndStoreQrCode(data.profil.qrCodeFile, data.profil.slug);
                        // await this.fetchAndStoreQrCode(data.profil.qrCodeFile, data.profil.slug);
                    } catch (e) {
                        console.warn("⚠️ Échec téléchargement QR Code :", e);
                    }

                    resolve();
                };

                tx.onerror = (e) => reject(e.target.error);
            };

            request.onerror = (e) => reject(e.target.error);
        });
    }

    async fetchAndStoreQrCode(url, slug) {
        if (!url) return console.warn("⚠️ Aucun QR Code à télécharger");

        const absoluteUrl = url.startsWith('http')
            ? url
            : `${window.location.origin}/qrcode/${url.replace(/^\/+/, '')}`;

        console.log("📡 Téléchargement du QR Code depuis :", absoluteUrl);

        try {
            const response = await fetch(absoluteUrl);
            if (!response.ok) throw new Error(`Erreur téléchargement (${response.status})`);

            const blob = await response.blob();
            const blobUrl = URL.createObjectURL(blob);

            // On sauvegarde le blob dans une transaction séparée
            const request = indexedDB.open(DB_NAME, DB_VERSION);
            request.onsuccess = (event) => {
                const db = event.target.result;
                const tx = db.transaction(['profil'], 'readwrite');
                const store = tx.objectStore('profil');

                const getReq = store.get(slug);
                getReq.onsuccess = () => {
                    const profil = getReq.result;
                    if (profil) {
                        profil.qrCodeBlob = blobUrl;
                        store.put(profil);
                        console.log("📸 QR Code sauvegardé localement !");
                    }
                };
            };
        } catch (e) {
            console.error("⚠️ Échec du téléchargement du QR Code :", e);
        }
    }


    /**
     * Convertit un Blob en chaîne base64
     */
    blobToBase64(blob) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    }
}
