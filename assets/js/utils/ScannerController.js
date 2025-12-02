import { Capacitor } from "@capacitor/core";
import { BarcodeScanner } from "@capacitor-community/barcode-scanner";
import { Html5Qrcode } from "html5-qrcode";

export default class ScannerController {

    constructor(options = {}) {
        this.onScan = options.onScan || function () {};
        this.html5QrCode = null;
        this.webContainerId = options.webContainerId || "qr-reader";
        this.isScanning = false;
    }

    //----------------------------------------------------------------------
    // Detect Platform
    //----------------------------------------------------------------------
    isNative() {
        return Capacitor.isNativePlatform();
    }

    //----------------------------------------------------------------------
    // PUBLIC : Start scanning (auto-select platform)
    //----------------------------------------------------------------------
    async start() {
        if (this.isScanning) return;
        this.isScanning = true;

        if (this.isNative()) {
            return this.scanNative();
        } else {
            return this.scanWeb();
        }
    }

    //----------------------------------------------------------------------
    // PUBLIC : Stop scanning (works for both modes)
    //----------------------------------------------------------------------
    async stop() {
        this.isScanning = false;

        // Stop Native
        try { BarcodeScanner.stopScan(); } catch (e) {}

        // Stop Web
        if (this.html5QrCode) {
            try { await this.html5QrCode.stop(); } catch (e) {}
            this.html5QrCode = null;
        }
    }

    //----------------------------------------------------------------------
    // Native Scan (Capacitor)
    //----------------------------------------------------------------------
    async scanNative() {
        try {
            const status = await BarcodeScanner.checkPermission({ force: true });

            if (!status.granted) {
                alert("Permission caméra refusée");
                return;
            }

            BarcodeScanner.hideBackground();

            const result = await BarcodeScanner.startScan();

            if (result.hasContent) {
                this.onScan(result.content);
            }
        } catch (error) {
            console.error("Erreur scan natif :", error);
        } finally {
            BarcodeScanner.showBackground();
            BarcodeScanner.stopScan();
            this.isScanning = false;
        }
    }

    //----------------------------------------------------------------------
    // Web Scan (navigator + html5-qrcode)
    //----------------------------------------------------------------------
    async scanWeb() {
        try {
            // Vérifier si Web support caméra
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert("Caméra non supportée sur ce navigateur.");
                return;
            }

            this.html5QrCode = new Html5Qrcode(this.webContainerId);

            await this.html5QrCode.start(
                { facingMode: "environment" },
                {
                    fps: 12,
                    qrbox: 250
                },
                (decodedText) => {
                    this.onScan(decodedText);
                    this.stop(); // stop after first scan
                }
            );

        } catch (error) {
            console.error("Erreur scan web :", error);
            this.isScanning = false;
        }
    }
}
