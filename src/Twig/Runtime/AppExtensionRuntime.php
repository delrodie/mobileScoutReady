<?php

namespace App\Twig\Runtime;

use App\Enum\FonctionPoste;
use App\Enum\ScoutStatut;
use App\Repository\FonctionRepository;
use App\Repository\ScoutRepository;
use App\Services\UtilityService;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

class AppExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly FonctionRepository $fonctionRepository,
        private readonly UtilityService $utilityService,
        private readonly RequestStack $requestStack,
    )
    {
        // Inject dependencies if needed
    }

    public function fonctionScoute($value)
    {
        $fonction = $this->fonctionRepository->findOneBy([
            'scout' => $value,
            'annee' => $this->utilityService->annee()
        ]);


        $statut = $fonction->getScout()->getStatut();
        return match ($statut) {
            ScoutStatut::ADULTE => FonctionPoste::from($fonction->getPoste())->label() .' - '.$fonction->getDetailPoste(),
            default => $fonction->getBranche()
        };

    }

    public function historiqueNavigation(): ?string
    {
        return $this->requestStack->getCurrentRequest()?->headers->get('referer');
    }
}
