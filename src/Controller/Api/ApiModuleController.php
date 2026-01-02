<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\ChampActivite;
use App\Mapper\ReunionMapper;
use App\Repository\FonctionRepository;
use App\Repository\InstanceRepository;
use App\Repository\ReunionRepository;
use App\Repository\ScoutRepository;
use App\Services\GestionInstance;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/module')]
class ApiModuleController extends AbstractController
{
    public function __construct(
        private readonly ScoutRepository    $scoutRepository,
        private readonly InstanceRepository $instanceRepository,
        private readonly ReunionMapper      $reunionMapper, private readonly GestionInstance $gestionInstance, private readonly ReunionRepository $reunionRepository, private readonly FonctionRepository $fonctionRepository
    )
    {
    }

    #[Route('/{champ}', name: 'api_module_champ', methods: ['GET','POST'])]
    public function champ(Request $request, ChampActivite $champ): Response
    {
        $requestData = json_decode($request->getContent(), true);
        $slug = $requestData['slug'] ?? null;
        $code = $requestData['code'] ?? null;
        $instanceId = $requestData['instance'] ?? null;

        if (!$slug || !$code || !$instanceId) {
            notyf()->error("Paramètres manquants, veuillez vous déconnecter puis ous réconnecter.");
            return $this->json(['error' => 'Paramètres manquants'], Response::HTTP_BAD_REQUEST);
        }

        $profilConnecte = $this->scoutRepository->findOneBy(['slug' => $slug]);
        if (!$profilConnecte){
            notyf()->warning("Votre profil est introuvable. Veuillez vous deconnecter puis vous reconnecter");
            return $this->json(['error' => "Profil introuvable"], Response::HTTP_NOT_FOUND);
        }

        // Gestion des instances
        $instance = $this->instanceRepository->findOneBy(['id' => (int) $instanceId]);
        if(!$instance){
            notyf()->warning("Votre profil n'est associé a aucune instance. Veuillez vous deconnecter puis vous reconnecter");
            return $this->json(['error' => "Instance introuvable"], Response::HTTP_NOT_FOUND);
        }

        // reunions
        $fonction = $this->fonctionRepository->findOneBy(['scout' => $profilConnecte],['id' => "DESC"]);
        $branche = null;
        if ($profilConnecte->getStatut()->name === 'JEUNE'){
            $branche = $fonction->getBranche();
        }

        $reunions = $this->getReunionByInstance($instance, $champ->getId(), $branche);

        $data = array_map(fn($r) => $this->reunionMapper->toDto($r), $reunions);
//        dump($data);

        return $this->json(['data' => $data], Response::HTTP_OK);

    }

    protected function getReunionByInstance(object $instance, int $champ, ?string $branche = null)
    {
        // Recupération des Ids utiles
        $ids = $this->gestionInstance->resolveInstanceIds($instance);
        return $this->reunionRepository->findReunionByInstanceAndChamp($champ, $ids, $branche);
    }

}
