<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AutorisationPointageReunion;
use App\Entity\Reunion;
use App\Entity\Scout;
use App\Enum\AutorisationPointeur;
use App\Form\AutorisationReunionType;
use App\Form\ReunionType;
use App\Repository\AutorisationPointageReunionRepository;
use App\Repository\FonctionRepository;
use App\Repository\InstanceRepository;
use App\Repository\ReunionRepository;
use App\Repository\ScoutRepository;
use App\Services\UtilityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reunion')]
class ReunionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReunionRepository      $reunionRepository,
        private readonly ScoutRepository        $scoutRepository,
        private readonly InstanceRepository     $instanceRepository,
        private readonly UtilityService         $utilityService,
        private readonly FonctionRepository $fonctionRepository,
        private readonly AutorisationPointageReunionRepository $pointageReunionRepository
    )
    {
    }

    #[Route('/', name: 'app_reunion_index')]
    public function index(Request $request): Response
    {
        return $this->render('reunion/index.html.twig');
    }

    #[Route('/new', name: 'app_reunion_new', methods: ['GET','POST'])]
    public function new(Request $request)
    {
        $reunion = new Reunion();
        $form = $this->createForm(ReunionType::class, $reunion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reqSlug = $request->get('_slug');
            $reqInstance = $request->get('_instance');

            $createur = $this->scoutRepository->findOneBy(['slug' => $reqSlug]);
            if (!$createur){
                notyf()->error("Votre profil n'a pas été trouvé. Veuillez vous deconnecter puis vous réconnecter");
                return $this->redirectToRoute('app_reunion_new');
            }

            $instance = $this->instanceRepository->findOneBy(['id' => (int) $reqInstance]);
            if (!$instance){
                notyf()->error("Votre profil n'est associé à aucune instance. Veuillez vous déconnecter puis vous reconnecter");
                return $this->redirectToRoute('app_reunion_new');
            }

            $reunion->setCode($this->utilityService->generationCode());
            $reunion->setInstance($instance);
            $reunion->setCreatedAt(new \DateTimeImmutable());
            $reunion->setCreatedBy($createur->getCode());

            // Initialisation de l'autorisation
            $this->autorisationPointage($createur, $reunion, AutorisationPointeur::CREATEUR->value);

            $this->entityManager->persist($reunion);
            $this->entityManager->flush();

            notyf()->success("Reunion enregistrée avec succès. veuillez enregistrer les personnes autorisées à faire le pointage");

            return $this->redirectToRoute('app_reunion_show',['id' => $reunion->getId()]);
        }

        return $this->render('reunion/new.html.twig', [
            'reunion' => $reunion,
            'form' => $form
        ]);
    }

    #[Route('/{id}', name: 'app_reunion_show', methods: ['GET'])]
    public function show(Reunion $reunion): Response
    {
        $fonction = $this->fonctionRepository->findOneByScoutCode($reunion?->getCreatedBy());
        $auteur = [
            'id' => $fonction?->getScout()->getId(),
            'nom' => $fonction?->getScout()->getNom(). ' '. $fonction?->getScout()->getPrenom(),
            'poste' => $fonction?->getDetailPoste()
        ];

        $pointage = $this->pointageReunionRepository->findOneBy([
            'scout' => $fonction?->getScout(),
            'reunion' => $reunion
        ]);

        return $this->render('reunion/show.html.twig', [
            'reunion' => $reunion,
            'auteur_nom' => $auteur['nom'],
            'auteur_poste' => $auteur['poste'],
            'autorisation' => $pointage,
            'pointeurs' => $this->pointageReunionRepository->findBy(['reunion' => $reunion]),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reunion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reunion $reunion): Response
    {
        $form = $this->createForm(ReunionType::class, $reunion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            notyf()->success("La reunion a été modifiée avec succès!");

            return $this->redirectToRoute('app_reunion_show', ['id' => $reunion->getId()]);
        }

        return $this->render('reunion/edit.html.twig', [
            'reunion' => $reunion,
            'form' => $form
        ]);
    }


    /**
     * Persistance de la table Autorisation pointage reunion
     * @param Scout $createur
     * @param Reunion $reunion
     * @param string $value
     * @return void
     */
    private function autorisationPointage(Scout $createur, Reunion $reunion, string $value): void
    {
        $autorisation = new AutorisationPointageReunion();
        $autorisation->setScout($createur);
        $autorisation->setReunion($reunion);
        $autorisation->setRole($value);
        $autorisation->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($autorisation);
    }
}
