<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.20',
    ],
    'tom-select' => [
        'version' => '2.4.3',
    ],
    '@orchidjs/sifter' => [
        'version' => '1.1.0',
    ],
    '@orchidjs/unicode-variants' => [
        'version' => '1.1.2',
    ],
    'tom-select/dist/css/tom-select.default.min.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
    'tom-select/dist/css/tom-select.default.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
    'tom-select/dist/css/tom-select.bootstrap4.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
    'tom-select/dist/css/tom-select.bootstrap5.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
    '@capacitor/core' => [
        'version' => '8.0.0',
    ],
    'html5-qrcode' => [
        'version' => '2.3.8',
    ],
    '@flasher/flasher' => [
        'version' => '2.2.0',
    ],
    '@capacitor/camera' => [
        'version' => '7.0.2',
    ],
    '@capacitor/barcode-scanner' => [
        'version' => '3.0.0',
    ],
    '@capacitor/toast' => [
        'version' => '8.0.0',
    ],
    '@symfony/ux-live-component' => [
        'path' => './vendor/symfony/ux-live-component/assets/dist/live_controller.js',
    ],
    '@capacitor/push-notifications' => [
        'version' => '8.0.0',
    ],
    '@capacitor/device' => [
        'version' => '8.0.0',
    ],
    '@capacitor/local-notifications' => [
        'version' => '8.0.0',
    ],
    'firebase/app' => [
        'version' => '12.9.0',
    ],
    '@firebase/app' => [
        'version' => '0.14.6',
    ],
    '@firebase/component' => [
        'version' => '0.7.0',
    ],
    '@firebase/logger' => [
        'version' => '0.5.0',
    ],
    '@firebase/util' => [
        'version' => '1.13.0',
    ],
    'idb' => [
        'version' => '7.1.1',
    ],
    'firebase/auth' => [
        'version' => '12.9.0',
    ],
    '@firebase/auth' => [
        'version' => '1.12.0',
    ],
    'bootstrap' => [
        'version' => '5.3.8',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.8',
        'type' => 'css',
    ],
];
