<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Activite;
use App\Entity\AutorisationPointageActivite;
use App\Entity\Notification;
use App\Entity\Scout;
use App\Enum\AutorisationPointeur;
use App\Form\ActiviteType;
use App\Repository\ActiviteRepository;
use App\Repository\AutorisationPointageActiviteRepository;
use App\Repository\InstanceRepository;
use App\Repository\ParticiperRepository;
use App\Repository\ScoutRepository;
use App\Services\GestionAffiche;
use App\Services\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/activites')]
class ActiviteController extends AbstractController
{
    public function __construct(
        private readonly ScoutRepository        $scoutRepository,
        private readonly InstanceRepository     $instanceRepository,
        private readonly ActiviteRepository     $activiteRepository,
        private readonly GestionAffiche         $gestionAffiche,
        private readonly EntityManagerInterface $entityManager, private readonly AutorisationPointageActiviteRepository $autorisationPointageActiviteRepository, private readonly ParticiperRepository $participerRepository, private readonly UrlGeneratorInterface $urlGenerator, private readonly NotificationService $notificationService
    )
    {
    }

    #[Route('/', name:'app_activite_index')]
    public function index(): Response
    {
        return $this->render('activite/index.html.twig');
    }

    #[Route('/{id}', name: 'app_activite_show', methods: ['GET','POST'])]
    public function show(Activite $activite): Response
    {
        return $this->render('activite/show.html.twig', [
            'activite' => $activite,
            'pointeurs' => $this->autorisationPointageActiviteRepository->findPointeurs($activite->getId()),
            'participants' =>$this->participerRepository->findOneBy(['activite' => $activite])
        ]);
    }

    #[Route('/new/nouveau', name:'app_activite_new', methods: ['GET','POST'])]
    public function new(Request $request)
    {
        $activite = new Activite();
        $form = $this->createForm(ActiviteType::class, $activite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $reqSlug = $request->get("_slug");
            $reqInstance = $request->get("_instance");

            $scout = $this->scoutRepository->findOneBy(['slug' => $reqSlug]);
            if (!$scout){
                notyf()->error("Aucun profil trouvé. Veuillez vous reconnecter!", [], "Echèc");
                return $this->redirectToRoute('app_search_phone');
            }

            $instance = $this->instanceRepository->findOneBy(['id' => (int) $reqInstance]);
            if (!$instance){
                notyf()->error("Votre profil n'est associé à aucune instance. Veuillez vous reconnecter!");
            }

            // Gestion de l'affiche
            $this->gestionAffiche->media($form, $activite);
            $activite->setInstance($instance);

            // Ajout de l'operateur comme personne qui peut pointer
            $createur = new AutorisationPointageActivite();
            $createur->setScout($scout);
            $createur->setActivite($activite);
            $createur->setRole(AutorisationPointeur::CREATEUR->value);
            $createur->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($createur);
            $this->entityManager->persist($activite);
            $this->entityManager->flush();

            $this->notificationService->notifierActivite($activite);

            notyf()->success("L'activité a été enregistrée avec succès!");

            return $this->redirectToRoute('app_activite_show',['id' => $activite->getId()]);
        }

        return $this->render('activite/new.html.twig',[
            'activite' => $activite,
            'form' => $form
        ]);
    }

    #[Route('/{id}/edit/activite', name:'app_activite_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Activite $activite): Response
    {
        $ancienneAffiche = $activite->getAffiche();
        $ancienTdr = $activite->getTdr();

        $form = $this->createForm(ActiviteType::class, $activite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            if (!$form->get('affiche')->getData()) {
                $activite->setAffiche($ancienneAffiche);
            } else {
                $this->gestionAffiche->media($form, $activite);
            }

            $this->entityManager->flush();

            $this->notificationService->notifierActivite($activite);

            notyf()->success("L'activité a été mise à jour !");

            return $this->redirectToRoute('app_activite_show', ['id' => $activite->getId()]);
        }

        return $this->render('activite/edit.html.twig', [
            'activite' => $activite,
            'form' => $form
        ]);
    }

    #[Route('/{id}/participants', name: 'app_activite_participant', methods: ['GET'])]
    public function participant(Activite $activite): Response
    {
        return $this->render('activite/participants.html.twig', [
            'activite' => $activite,
            'participants' => $this->participerRepository->findPresenceByActivite($activite->getId()),
        ]);
    }

    /**
     * Crée et lie une entité AutorisationPointageActivite pour un scout donné.
     * @param Activite $activite
     * @param Scout $scout
     * @param string $role
     * @return void
     */
    private function addAutorisation(Activite $activite, Scout $scout, string $role): void
    {
        $autorisation = new AutorisationPointageActivite();
        $autorisation->setScout($scout);
        $autorisation->setActivite($activite);
        $autorisation->setRole($role);
        $autorisation->setCreatedAt(new \DateTimeImmutable());

        $activite->addAutorisation($autorisation);
        $this->entityManager->persist($autorisation);
    }
}
