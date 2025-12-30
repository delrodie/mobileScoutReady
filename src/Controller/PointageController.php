<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Assister;
use App\Entity\Participer;
use App\Repository\ActiviteRepository;
use App\Repository\AssisterRepository;
use App\Repository\AutorisationPointageActiviteRepository;
use App\Repository\AutorisationPointageReunionRepository;
use App\Repository\ParticiperRepository;
use App\Repository\ReunionRepository;
use App\Repository\ScoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pointage')]
class PointageController extends AbstractController
{
    const PROFIL_INTROUVABLE = "Votre profil est introuvable. Veuillez vous déconnecter puis reconnecter si l'erreur persiste.";
    const ACTIVITE_INTROUVABLE = "L'activité concernée n'a pas été trouvée";
    const REUNION_INTROUVABLE = "La réunion concernée n'a pas été trouvée";
    const SCOUT_INTROUVABLE = "Le scout concerné n'a pas été trouvé";
    const NON_AUTORISE = "Echèc! Vous n'êtes pas autorisé(e) à pointer cette reunion.";
    const PARTICIPANT_SCANNE = "Attention, ce participant a déjà été scanné";

    public function __construct(
        private readonly ScoutRepository                        $scoutRepository,
        private readonly ActiviteRepository                     $activiteRepository,
        private readonly AutorisationPointageActiviteRepository $autorisationPointageActiviteRepository,
        private readonly ParticiperRepository                   $participerRepository,
        private readonly EntityManagerInterface                 $entityManager,
        private readonly ReunionRepository $reunionRepository,
        private readonly AutorisationPointageReunionRepository $autorisationPointageReunionRepository,
        private readonly AssisterRepository $assisterRepository
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
            notyf()->error(self::PROFIL_INTROUVABLE);
            return $this->json([
                'status' => 'error',
                'message' => self::PROFIL_INTROUVABLE
            ], Response::HTTP_NOT_FOUND);
        }

        $activite = $this->activiteRepository->findOneBy(['id' => (int) $activiteId]);
        if(!$activite) {
            notyf()->error(self::ACTIVITE_INTROUVABLE);
            return $this->json([
                'status' => 'error',
                'message' => self::ACTIVITE_INTROUVABLE
            ], Response::HTTP_NOT_FOUND);
        }

        $scout = $this->scoutRepository->findOneBy(['qrCodeToken' => $code]);
        if (!$scout) {
            notyf()->error(self::SCOUT_INTROUVABLE);
            return $this->json([
                'status' => "error",
                'message' => self::SCOUT_INTROUVABLE
            ], Response::HTTP_NOT_FOUND);
        }

        // Verifier si le pointeur a l'autorisation de scanner
        $verificationAutorisation = $this->autorisationPointageActiviteRepository->findOneBy([
            'scout' => $pointeur,
            'activite' => $activite
        ]);

        if (!$verificationAutorisation){
            notyf()->error(self::NON_AUTORISE);
            return $this->json([
                'error' => self::NON_AUTORISE
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
                    'message' => self::PARTICIPANT_SCANNE
                ], Response::HTTP_CONFLICT);
            }

            notyf()->warning(self::PARTICIPANT_SCANNE);
            return $this->json([
                'status' => 'warning',
                'message' => self::PARTICIPANT_SCANNE
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

    #[Route('/reunion', name:'app_pointage_reunion', methods: ['GET','POST'])]
    public function reunion(Request $request): Response
    {
        $reunionId = $request->get('reunion');
        $pointeurCode = $request->get('pointeur');
        $code = $request->get('code');

        $pointeur = $this->scoutRepository->findOneBy(['code' => $pointeurCode]);
        if(!$pointeur){
            notyf()->error(self::PROFIL_INTROUVABLE);
            return $this->json([
                'status' => 'error',
                'message' => self::PROFIL_INTROUVABLE
            ], Response::HTTP_NOT_FOUND);
        }

        $reunion = $this->reunionRepository->findOneBy(['id' => (int) $reunionId]);
        if (!$reunion){
            notyf()->error(self::REUNION_INTROUVABLE);
            return $this->json([
                'status' => 'error',
                'message' => self::REUNION_INTROUVABLE
            ], Response::HTTP_NOT_FOUND);
        }

        $scout = $this->scoutRepository->findOneBy(['qrCodeToken' => $code]);
        if(!$scout){
            notyf()->error(self::SCOUT_INTROUVABLE);
            return $this->json([
                'status' => 'error',
                'message' => self::SCOUT_INTROUVABLE
            ], Response::HTTP_NOT_FOUND);
        }

        // Verifier si le pointeur a l'autorisation de scanner
        $verificationAutorisation = $this->autorisationPointageReunionRepository->findOneBy([
            'scout' => $pointeur,
            'reunion' => $reunion
        ]);

        if(!$verificationAutorisation){
            notyf()->error(self::NON_AUTORISE);
            return $this->json([
                'status' => 'error',
                'message' => self::NON_AUTORISE,
            ], Response::HTTP_BAD_REQUEST);
        }

        $dejaPointe = $this->assisterRepository->findOneBy([
            'scout' => $scout,
            'reunion' => $reunion
        ]);

        if ($dejaPointe){
            if ($request->isXmlHttpRequest()){
                return $this->json([
                    'status' => 'warning',
                    'message' => self::PARTICIPANT_SCANNE,
                ], Response::HTTP_CONFLICT);
            }

            notyf()->warning(self::PARTICIPANT_SCANNE);
            return $this->json([
                'status' => "warning",
                'message' => self::PARTICIPANT_SCANNE
            ]);
        }

        // Creation de la nouvelle participation à la reunion
        $participation = new Assister();
        $participation->setReunion($reunion);
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
