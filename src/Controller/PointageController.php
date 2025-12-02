<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Participer;
use App\Repository\ActiviteRepository;
use App\Repository\AutorisationPointageActiviteRepository;
use App\Repository\ParticiperRepository;
use App\Repository\ScoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pointage')]
class PointageController extends AbstractController
{
    public function __construct(
        private readonly ScoutRepository                        $scoutRepository,
        private readonly ActiviteRepository                     $activiteRepository,
        private readonly AutorisationPointageActiviteRepository $autorisationPointageActiviteRepository,
        private readonly ParticiperRepository                   $participerRepository, private readonly EntityManagerInterface $entityManager
    )
    {
    }

    #[Route('/', name: 'app_pointage_activite', methods: ['GET','POST'])]
    public function activite(Request $request): Response
    {
        $activiteId = $request->get('activite');
        $pointeurCode = $request->get('pointeur');
        $code = $request->get('code');

        $pointeur = $this->scoutRepository->findOneBy(['code' => $pointeurCode]);
        if (!$pointeur) {
            flash()->error("Votre profil est introuvable. Veuillez vous déconnecter puis reconnecter si l'erreur persiste.", ['position', 'bottom-right'], 'Échec');
            return $this->json(['status' => 'error', 'message' => 'Profil introuvabel'], Response::HTTP_NOT_FOUND);
        }

        $activite = $this->activiteRepository->findOneBy(['id' => (int) $activiteId]);
        if(!$activite) {
            flash()->error("L'activité concernée n'a pas été trouvée!", [], 'Erreur');
            return $this->json(['status' => 'error', 'message' => 'Activité introuvable'], Response::HTTP_NOT_FOUND);
        }

        $scout = $this->scoutRepository->findOneBy(['qrCodeToken' => $code]);
        if (!$scout) {
            flash()->error("Le scout concerné n'a pas été trouvé.", ['position', 'bottom-right'], "Echec");
            return $this->json(['error' => "Scout introuvable"], Response::HTTP_NOT_FOUND);
        }

        // Vérification de l'autorisation de pointage
//        $auth = $this->autorisationPointageActiviteRepository->findOneBy([
//            'scout' => $pointeur,
//            'activite' => $activite
//        ]);
//        if (!$auth){
//            flash()->error("Vous n'avez pas l'autorisation de pointer à cette activité. Veuillez contacter l'organisateur principal ", [], "Opération refusée");
//            return $this->json(['status' => "warning", 'message' => "Operation réfusée"], Response::HTTP_FORBIDDEN);
//        }

        // Verification de non existence de pointage
        $dejaPointe = $this->participerRepository->findOneBy([
            'scout' => $scout,
            'activite' => $activite
        ]);

        if ($dejaPointe){
            $this->json([
                'status' => 'warning',
                'message' => 'Déjà pointé'
            ], Response::HTTP_CONFLICT);
        }

        // Creation de la nouvelle participation
        $participation = new Participer();
        $participation->setActivite($activite);
        $participation->setScout($scout);
        $participation->setPointageAt(new \DateTimeImmutable());

        $this->entityManager->persist($participation);
        $this->entityManager->flush();

        flash()->success("Scout pointé avec succès!", ['position', 'bottom-right'], "Success");
        return $this->json(['status' => 'success'], Response::HTTP_OK);

    }
}
