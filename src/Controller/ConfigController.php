<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/configurations')]
class ConfigController extends AbstractController
{
    #[Route('/android_v1.json', name: 'app_android_config', methods: ['GET'])]
    public function androidV1(): JsonResponse
    {
        // Contenu du JSON (doit correspondre à la version locale)
        $config = [
            'settings' => [
                'screenshots_enabled' => true,
            ],
            'rules' => [
                [
                    'patterns' => ['/new$', '/edit$'],
                    'properties' => [
                        'context' => 'modal',
                        'uri' => 'hotwire://fragment/web/modal/sheet',
                        'pull_to_refresh_enabled' => false,
                        "title" => "",
                        "toolbar_hidden" => true
                    ],
                ],
                [
                    'patterns' => ['/accueil$', '/activites$', '/communaute$'],
                    'properties' => [
                        'context' => 'default',
                        'uri' => 'hotwire://fragment/web/',
                        'pull_to_refresh_enabled' => true,
                    ],
                ],
                [
                    'patterns' => ['/fonctionnalites$'],
                    'properties' => [
                        'context' => 'default',
                        'uri' => 'hotwire://fragment/web',
                        'pull_to_refresh_enabled' => false,
                    ],
                ],
                [
                    'patterns' => ['.*'],
                    'properties' => [
                        'context' => 'default',
                        'uri' => 'hotwire://fragment/web',
                        'pull_to_refresh_enabled' => true,
                    ],
                ]
            ],
        ];

        return new JsonResponse($config);
    }
}
