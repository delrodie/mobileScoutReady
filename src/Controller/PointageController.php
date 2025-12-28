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

        $pointeur = $this->scoutRepository->findOneBy(['code' => $pointeurCode]); //dump($pointeur);
        if (!$pointeur) {
            notyf()->error("Votre profil est introuvable. Veuillez vous déconnecter puis reconnecter si l'erreur persiste.");
            return $this->json([
                'status' => 'error',
                'message' => 'Votre profil est introuvable. Veuillez vous déconnecter puis reconnecter si l\'erreur persiste.'
            ], Response::HTTP_NOT_FOUND);
        }

        $activite = $this->activiteRepository->findOneBy(['id' => (int) $activiteId]);
        if(!$activite) {
            notyf()->error("L'activité concernée n'a pas été trouvée!");
            return $this->json([
                'status' => 'error',
                'message' => "L'activité concernée n'a pas été trouvée!"
            ], Response::HTTP_NOT_FOUND);
        }

        $scout = $this->scoutRepository->findOneBy(['qrCodeToken' => $code]);
        if (!$scout) {
            notyf()->error("Le scout concerné n'a pas été trouvé.");
            return $this->json(['error' => "Scout introuvable"], Response::HTTP_NOT_FOUND);
        }

        // Verifier si le pointeur a l'autorisation de scanner
        $verificationAutorisation = $this->autorisationPointageActiviteRepository->findOneBy([
            'scout' => $pointeur,
            'activite' => $activite
        ]);

        if (!$verificationAutorisation){
            notyf()->error("Echèc! Vous n'êtes pas autorisé(e) à pointer à cette activité. ");
            return $this->json([
                'error' => "Echèc! Vous n'êtes pas autorisé(e) à pointer à cette activité. "
            ], Response::HTTP_BAD_REQUEST);
        }
        /// si oui faire la mise a jour de la table


        // Verification de non existence de pointage
        $dejaPointe = $this->participerRepository->findOneBy([
            'scout' => $scout,
            'activite' => $activite
        ]);

        if ($dejaPointe){

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'status' => 'warning',
                    'message' => 'Attention, ce participant a déjà été scanné'
                ], Response::HTTP_CONFLICT);
            }

            notyf()->warning("Attention, ce participant a déjà été scanné");
            return $this->json([
                'status' => 'warning',
                'message' => "Attention, ce participant a déjà été scanné"
            ], Response::HTTP_CONFLICT);
        }

        // Creation de la nouvelle participation
        $participation = new Participer();
        $participation->setActivite($activite);
        $participation->setScout($scout);
        $participation->setPointageAt(new \DateTimeImmutable());

        $this->entityManager->persist($participation);
        $this->entityManager->flush();

        notyf()->success("Scout pointé avec succès!");
        return $this->json([
            'status' => 'success',
            'message' => "Scout pointé avec succès!"
        ], Response::HTTP_OK);

    }
}
