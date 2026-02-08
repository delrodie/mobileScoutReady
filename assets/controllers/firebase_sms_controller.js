import { Controller } from '@hotwired/stimulus';
import { initializeApp } from 'firebase/app';
import { getAuth, RecaptchaVerifier, signInWithPhoneNumber } from 'firebase/auth';
import { Device } from '@capacitor/device';
import { Capacitor } from '@capacitor/core';
import { Toast } from '@capacitor/toast';
import { firebaseConfig} from "../firebaseConfig.js";

/**
 * Contrôleur Firebase SMS OTP - VERSION CORRIGÉE
 */
export default class extends Controller {

    connect() {
        console.log("🔥 Firebase SMS Controller - Démarrage");
        console.log("📍 Plateforme:", Capacitor.getPlatform());
        console.log("📱 Natif?", Capacitor.isNativePlatform());

        // Initialiser Firebase immédiatement
        this.initializeFirebase();
    }

    /**
     * Initialise Firebase avec votre configuration
     */
    initializeFirebase() {
        try {
            console.log("⚙️ Initialisation Firebase...");

            // ✅ VOTRE CONFIGURATION FIREBASE


            console.log("📋 Config Firebase:", {
                projectId: firebaseConfig.projectId,
                authDomain: firebaseConfig.authDomain
            });

            // Initialiser Firebase
            const app = initializeApp(firebaseConfig);
            this.auth = getAuth(app);
            this.auth.languageCode = 'fr';

            console.log("✅ Firebase Auth initialisé avec succès");
            console.log("🔐 Auth object:", this.auth);

        } catch (error) {
            console.error("❌ ERREUR FATALE Firebase Init:", error);
            console.error("Message:", error.message);
            console.error("Stack:", error.stack);

            this.showToast("❌ Erreur Firebase: " + error.message);
        }
    }

    /**
     * Envoie un SMS OTP via Firebase
     */
    async sendSmsOtp(phoneNumber) {
        console.log("\n==========================================");
        console.log("📤 DÉBUT ENVOI SMS");
        console.log("Numéro reçu:", phoneNumber);
        console.log("==========================================");

        try {
            // 1. Vérifier que Firebase est prêt
            if (!this.auth) {
                console.error("❌ Firebase Auth n'est pas initialisé!");
                throw new Error("Firebase non initialisé");
            }
            console.log("✅ Firebase Auth prêt");

            // 2. Formater le numéro
            const formattedPhone = this.formatPhoneNumber(phoneNumber);
            console.log("📱 Numéro formaté:", formattedPhone);

            // 3. Configurer reCAPTCHA (WEB uniquement)
            const isNative = Capacitor.isNativePlatform();
            console.log("🌐 Mode:", isNative ? "Mobile natif" : "Web");

            if (!isNative) {
                console.log("⚙️ Configuration reCAPTCHA (mode web)...");
                await this.setupRecaptcha();
                console.log("✅ reCAPTCHA prêt");
            } else {
                console.log("📱 Mode natif - pas de reCAPTCHA");
            }

            // 4. Afficher toast "envoi en cours"
            this.showToast("📨 Envoi du SMS...");

            // 5. ENVOYER LE SMS VIA FIREBASE
            console.log("🔥 Appel signInWithPhoneNumber...");
            console.log("   - Phone:", formattedPhone);
            console.log("   - reCAPTCHA:", this.recaptchaVerifier ? "Configuré" : "Non requis");

            const confirmationResult = await signInWithPhoneNumber(
                this.auth,
                formattedPhone,
                this.recaptchaVerifier || undefined
            );

            console.log("✅ SMS ENVOYÉ AVEC SUCCÈS!");
            console.log("Confirmation result:", confirmationResult);

            // 6. Sauvegarder pour vérification ultérieure
            window.confirmationResult = confirmationResult;

            // 7. Succès
            this.showToast("✅ SMS envoyé !");

            console.log("==========================================");
            console.log("✅ FIN ENVOI SMS - SUCCÈS");
            console.log("==========================================\n");

            return {
                success: true,
                phoneNumber: formattedPhone
            };

        } catch (error) {
            console.error("\n==========================================");
            console.error("❌ ERREUR ENVOI SMS");
            console.error("Code:", error.code);
            console.error("Message:", error.message);
            console.error("Stack:", error.stack);
            console.error("==========================================\n");

            // Messages d'erreur clairs
            let errorMessage = "Erreur inconnue";

            switch (error.code) {
                case 'auth/invalid-phone-number':
                    errorMessage = "Numéro invalide. Vérifiez le format";
                    console.error("💡 Format attendu: +225XXXXXXXXXX");
                    break;

                case 'auth/too-many-requests':
                    errorMessage = "Trop de tentatives. Attendez 1 heure";
                    break;

                case 'auth/quota-exceeded':
                    errorMessage = "Quota SMS dépassé";
                    console.error("💡 Vérifiez votre plan Firebase");
                    break;

                case 'auth/captcha-check-failed':
                    errorMessage = "Échec reCAPTCHA";
                    console.error("💡 Rechargez la page");
                    break;

                case 'auth/missing-phone-number':
                    errorMessage = "Numéro manquant";
                    break;

                default:
                    errorMessage = error.message || "Erreur lors de l'envoi du SMS";
            }

            this.showToast("❌ " + errorMessage);

            return {
                success: false,
                error: errorMessage,
                errorCode: error.code
            };
        }
    }

    /**
     * Vérifie le code OTP saisi par l'utilisateur
     */
    async verifySmsOtp(code) {
        console.log("\n==========================================");
        console.log("🔍 DÉBUT VÉRIFICATION CODE");
        console.log("Code reçu:", code);
        console.log("==========================================");

        try {
            // 1. Vérifier la session
            if (!window.confirmationResult) {
                console.error("❌ Pas de session active");
                throw new Error("Session expirée. Redemandez un code");
            }
            console.log("✅ Session active trouvée");

            // 2. Afficher toast
            this.showToast("⏳ Vérification...");

            // 3. VÉRIFIER LE CODE
            console.log("🔥 Appel confirmationResult.confirm()...");

            const result = await window.confirmationResult.confirm(code);

            console.log("✅ CODE VALIDÉ PAR FIREBASE!");
            console.log("User UID:", result.user.uid);
            console.log("User Phone:", result.user.phoneNumber);

            // 4. Succès
            this.showToast("✅ Code validé !");

            console.log("==========================================");
            console.log("✅ FIN VÉRIFICATION - SUCCÈS");
            console.log("==========================================\n");

            return {
                success: true,
                uid: result.user.uid,
                phoneNumber: result.user.phoneNumber
            };

        } catch (error) {
            console.error("\n==========================================");
            console.error("❌ ERREUR VÉRIFICATION CODE");
            console.error("Code:", error.code);
            console.error("Message:", error.message);
            console.error("==========================================\n");

            let errorMessage = "Code invalide";

            switch (error.code) {
                case 'auth/invalid-verification-code':
                    errorMessage = "Code incorrect";
                    break;
                case 'auth/code-expired':
                    errorMessage = "Code expiré";
                    break;
                case 'auth/session-expired':
                    errorMessage = "Session expirée";
                    break;
                default:
                    errorMessage = error.message || "Code invalide";
            }

            this.showToast("❌ " + errorMessage);

            return {
                success: false,
                error: errorMessage
            };
        }
    }

    /**
     * Configure reCAPTCHA pour le web
     */
    async setupRecaptcha() {
        // Si déjà configuré
        if (this.recaptchaVerifier) {
            console.log("♻️ reCAPTCHA déjà configuré");
            return;
        }

        try {
            console.log("⚙️ Configuration reCAPTCHA...");

            // Vérifier le conteneur
            let container = document.getElementById('recaptcha-container');
            if (!container) {
                console.warn("⚠️ Conteneur reCAPTCHA manquant - création");
                container = document.createElement('div');
                container.id = 'recaptcha-container';
                document.body.appendChild(container);
            }
            console.log("✅ Conteneur trouvé:", container);

            // Créer le verifier
            this.recaptchaVerifier = new RecaptchaVerifier(
                this.auth,
                'recaptcha-container',
                {
                    'size': 'normal', // visible pour debug
                    'callback': (response) => {
                        console.log('✅ reCAPTCHA résolu');
                    },
                    'expired-callback': () => {
                        console.warn('⚠️ reCAPTCHA expiré');
                        this.recaptchaVerifier = null;
                    }
                }
            );

            // Render
            await this.recaptchaVerifier.render();
            console.log("✅ reCAPTCHA rendu et prêt");

        } catch (error) {
            console.error("❌ Erreur reCAPTCHA:", error);
            throw error;
        }
    }

    /**
     * Formate le numéro au format international E.164
     */
    formatPhoneNumber(phoneNumber) {
        console.log("🔧 Formatage numéro...");
        console.log("   Input:", phoneNumber);

        // Nettoyer (garder que les chiffres)
        let phone = phoneNumber.replace(/[^0-9]/g, '');
        console.log("   Nettoyé:", phone);

        // Enlever le 0 initial
        if (phone.startsWith('0')) {
            phone = phone.substring(1);
            console.log("   Sans 0:", phone);
        }

        // Ajouter code pays Côte d'Ivoire
        if (!phone.startsWith('225')) {
            phone = '225' + phone;
            console.log("   Avec 225:", phone);
        }

        // Ajouter le +
        const formatted = '+' + phone;
        console.log("   ✅ Final:", formatted);

        return formatted;
    }

    /**
     * Récupère les infos du device
     */
    async getDeviceInfo() {
        try {
            console.log("📱 Récupération device info...");

            const info = await Device.getInfo();
            const id = await Device.getId();

            const deviceInfo = {
                deviceId: id.identifier,
                platform: info.platform,
                model: info.model || info.manufacturer,
                osVersion: info.osVersion
            };

            console.log("✅ Device info:", deviceInfo);
            return deviceInfo;

        } catch (error) {
            console.warn("⚠️ Erreur device info, fallback web:", error);

            return {
                deviceId: this.generateDeviceId(),
                platform: 'web',
                model: navigator.userAgent,
                osVersion: 'unknown'
            };
        }
    }

    /**
     * Génère un device ID unique
     */
    generateDeviceId() {
        let deviceId = localStorage.getItem('device_id');

        if (!deviceId) {
            deviceId = 'web_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('device_id', deviceId);
            console.log("🆕 Nouveau device ID:", deviceId);
        } else {
            console.log("♻️ Device ID existant:", deviceId);
        }

        return deviceId;
    }

    /**
     * Affiche un toast (compatible web et mobile)
     */
    showToast(message) {
        if (Capacitor.isNativePlatform()) {
            Toast.show({
                text: message,
                duration: 'short'
            });
        } else {
            console.log("📢", message);
            // Sur web, utiliser une alerte simple ou une lib comme toastr
            // Pour l'instant, juste console
        }
    }

    /**
     * Déconnexion Firebase
     */
    async signOut() {
        try {
            if (this.auth) {
                await this.auth.signOut();
            }
            window.confirmationResult = null;
            console.log("👋 Déconnexion Firebase");
        } catch (error) {
            console.error("Erreur déconnexion:", error);
        }
    }
}
